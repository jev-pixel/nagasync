<?php

namespace Tests\Feature;

use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['status' => 'success', 'data' => []])]); // admin widgets call the analytics service
    }

    private function doc(User $owner, string $status = 'pending'): DocumentRequest
    {
        return DocumentRequest::create([
            'user_id' => $owner->id, 'barangay_id' => $owner->barangay_id,
            'document_type' => 'Barangay Clearance', 'purpose' => 'employment',
            'tracking_number' => DocumentRequest::generateTrackingNumber(), 'status' => $status,
        ]);
    }

    public static function roles(): array
    {
        return array_combine(User::ROLES, array_map(fn ($r) => [$r], User::ROLES));
    }

    #[DataProvider('roles')]
    public function test_every_role_gets_a_complete_dashboard(string $role): void
    {
        $this->actingAs($this->userWithRole($role))->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Dashboard')
                ->has('hero.title')->has('metrics', 4)->has('gauges', 2)->has('week', 7));
    }

    public function test_the_week_marks_exactly_one_day_as_today(): void
    {
        $this->actingAs($this->userWithRole('citizen'))->get('/dashboard')
            ->assertInertia(fn (Assert $p) => $p->where('week', fn ($w) => collect($w)->where('today', true)->count() === 1));
    }

    public function test_citizens_only_see_their_own_figures(): void
    {
        $a = $this->userWithRole('citizen');
        $b = $this->userWithRole('citizen');
        $this->doc($a);
        $this->doc($a);
        $this->doc($b);

        $this->actingAs($a)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('metrics.0.value', 2));
    }

    public function test_staff_figures_are_limited_to_their_barangay(): void
    {
        $staff = $this->userWithRole('barangay_staff', $this->barangay('A'));
        $this->doc($this->userWithRole('citizen', $this->barangay('A')));
        $this->doc($this->userWithRole('citizen', $this->barangay('B')));

        $this->actingAs($staff)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('metrics.0.value', 1));
    }

    public function test_system_status_is_for_administrators_only(): void
    {
        $this->actingAs($this->userWithRole('citizen'))->get('/dashboard')->assertInertia(fn (Assert $p) => $p->missing('system'));
        $this->actingAs($this->userWithRole('admin'))->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('system', 4));
    }
}