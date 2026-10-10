<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test User', 'email' => 'test@example.com', 'phone' => '09171234567',
            'barangay_id' => $this->barangay()->id, 'privacy_consent' => true,
            'password' => 'password', 'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_registration_screen_lists_barangays(): void
    {
        $this->barangay();

        $this->get('/register')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Auth/Register')->has('barangays', 1));
    }

    public function test_new_users_can_register_with_barangay_phone_and_consent(): void
    {
        $response = $this->post('/register', $this->payload());

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('citizen', $user->role);
        $this->assertSame('+639171234567', $user->phone);
        $this->assertNotNull($user->barangay_id);
        $this->assertNotNull($user->privacy_consent_at);
        $this->assertSame(config('nagasync.privacy_notice_version'), $user->privacy_notice_version);
    }

    public function test_privileged_fields_in_the_request_are_ignored(): void
    {
        $this->post('/register', $this->payload(['role' => 'admin', 'resident_verified_at' => now()->toDateTimeString()]));

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('citizen', $user->role);
        $this->assertNull($user->resident_verified_at);
    }

    public function test_consent_is_required(): void
    {
        $this->post('/register', $this->payload(['privacy_consent' => false]))
            ->assertSessionHasErrors('privacy_consent');

        $this->assertSame(0, User::count());
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->post('/register', $this->payload(['phone' => '12345']))->assertSessionHasErrors('phone');
    }

    public function test_unknown_barangay_is_rejected(): void
    {
        $this->post('/register', $this->payload(['barangay_id' => 99999]))->assertSessionHasErrors('barangay_id');
    }

    public static function phoneFormats(): array
    {
        return [
            'local'         => ['09171234567'],
            'spaced'        => ['0917 123 4567'],
            'dashed'        => ['0917-123-4567'],
            'international' => ['+63 917 123 4567'],
            'no plus'       => ['639171234567'],
        ];
    }

    #[DataProvider('phoneFormats')]
    public function test_phone_numbers_are_normalised(string $input): void
    {
        $this->post('/register', $this->payload(['phone' => $input]));

        $this->assertSame('+639171234567', User::where('email', 'test@example.com')->value('phone'));
    }
}