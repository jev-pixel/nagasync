<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // The Command Center calls the analytics service; never hit the network in tests
        Http::fake(['*' => Http::response(['status' => 'success', 'data' => []])]);
    }

    public static function protectedPages(): array
    {
        return [
            'command center' => ['/admin/command-center', ['admin']],
            'manage polls'   => ['/admin/polls', ['admin']],
            'document queue' => ['/staff/documents', ['barangay_staff', 'admin']],
            'price entry'    => ['/staff/market-prices', ['barangay_staff', 'admin']],
            'treasury'       => ['/treasury/payments', ['treasury', 'admin']],
            'dispatch'       => ['/dispatch', ['mdrrmo', 'admin']],
            'residents' => ['/staff/residents', ['barangay_staff', 'admin']],
        ];
    }

    #[DataProvider('protectedPages')]
    public function test_only_permitted_roles_get_through(string $url, array $allowed): void
    {
        foreach (User::ROLES as $role) {
            $response = $this->actingAs($this->userWithRole($role))->get($url);

            in_array($role, $allowed, true) ? $response->assertOk() : $response->assertForbidden();
        }
    }

    public function test_guests_are_sent_to_login(): void
    {
        foreach (['/documents', '/payments', '/polls', '/emergency', '/market', '/admin/command-center'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_role_cannot_be_self_assigned_through_profile_update(): void
    {
        $user = $this->userWithRole('citizen');

        $this->actingAs($user)->patch('/profile', ['name' => 'X', 'email' => $user->email, 'role' => 'admin']);

        $this->assertSame('citizen', $user->fresh()->role);
    }
}