<?php

namespace Tests\Feature;

use App\Events\EmergencyAlertChanged;
use App\Models\EmergencyAlert;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmergencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([EmergencyAlertChanged::class]);
    }

    public function test_alert_is_accepted_without_a_location(): void
    {
        $citizen = $this->userWithRole('citizen');

        $this->actingAs($citizen)->post('/emergency', ['alert_type' => 'medical'])->assertSessionHasNoErrors();

        $alert = EmergencyAlert::firstOrFail();
        $this->assertNull($alert->latitude);
        $this->assertSame('active', $alert->status);
        Event::assertDispatched(EmergencyAlertChanged::class);
    }

    public function test_repeat_press_updates_the_open_alert_and_keeps_the_location(): void
    {
        $citizen = $this->userWithRole('citizen');

        $this->actingAs($citizen)->post('/emergency', [
            'alert_type' => 'fire', 'latitude' => 13.62, 'longitude' => 123.19, 'accuracy_m' => 12,
        ]);
        $this->actingAs($citizen)->post('/emergency', ['alert_type' => 'fire']); // GPS failed this time

        $this->assertSame(1, EmergencyAlert::count());
        $this->assertEqualsWithDelta(13.62, EmergencyAlert::first()->latitude, 0.0001);
    }

    public function test_invalid_coordinates_are_rejected(): void
    {
        $this->actingAs($this->userWithRole('citizen'))
            ->post('/emergency', ['alert_type' => 'fire', 'latitude' => 123, 'longitude' => 0])
            ->assertSessionHasErrors('latitude');
    }

    public function test_button_is_rate_limited(): void
    {
        $citizen = $this->userWithRole('citizen');

        foreach (range(1, 3) as $_) {
            $this->actingAs($citizen)->post('/emergency', ['alert_type' => 'other']);
        }

        $this->actingAs($citizen)->post('/emergency', ['alert_type' => 'other'])->assertStatus(429);
    }

    public function test_responder_can_dispatch_and_resolve_but_not_reopen(): void
    {
        $citizen = $this->userWithRole('citizen');
        $alert = EmergencyAlert::create(['user_id' => $citizen->id, 'barangay_id' => $citizen->barangay_id, 'alert_type' => 'medical']);
        $responder = $this->userWithRole('mdrrmo');

        $this->actingAs($responder)->patch("/dispatch/{$alert->id}", ['status' => 'dispatched']);
        $this->assertSame('dispatched', $alert->fresh()->status);
        $this->assertSame($responder->id, $alert->fresh()->acknowledged_by);

        $this->actingAs($responder)->patch("/dispatch/{$alert->id}", ['status' => 'resolved']);
        $this->actingAs($responder)->patch("/dispatch/{$alert->id}", ['status' => 'dispatched'])->assertStatus(422);
        $this->assertSame('resolved', $alert->fresh()->status);
    }

    public function test_citizens_cannot_use_the_dispatch_controls(): void
    {
        $citizen = $this->userWithRole('citizen');
        $alert = EmergencyAlert::create(['user_id' => $citizen->id, 'alert_type' => 'other']);

        $this->actingAs($citizen)->patch("/dispatch/{$alert->id}", ['status' => 'resolved'])->assertForbidden();
    }
}