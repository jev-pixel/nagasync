<?php

namespace Tests\Feature;

use App\Models\Poll;
use App\Models\PollBallot;
use Tests\TestCase;

class ResidentVerificationTest extends TestCase
{
    public function test_staff_can_verify_a_resident_of_their_barangay_and_it_is_audited(): void
    {
        $barangay = $this->barangay('A');
        $staff = $this->userWithRole('barangay_staff', $barangay);
        $resident = $this->userWithRole('citizen', $barangay);

        $this->actingAs($staff)->patch("/staff/residents/{$resident->id}", ['verified' => true])->assertRedirect();

        $fresh = $resident->fresh();
        $this->assertNotNull($fresh->resident_verified_at);
        $this->assertEquals($staff->id, $fresh->resident_verified_by);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $staff->id, 'action' => 'resident_verified', 'auditable_id' => $resident->id,
        ]);
    }

    public function test_staff_cannot_verify_residents_of_another_barangay(): void
    {
        $staff = $this->userWithRole('barangay_staff', $this->barangay('A'));
        $resident = $this->userWithRole('citizen', $this->barangay('B'));

        $this->actingAs($staff)->patch("/staff/residents/{$resident->id}", ['verified' => true])->assertForbidden();

        $this->assertNull($resident->fresh()->resident_verified_at);
    }

    public function test_only_citizens_can_be_verified(): void
    {
        $barangay = $this->barangay('A');
        $staff = $this->userWithRole('barangay_staff', $barangay);
        $otherStaff = $this->userWithRole('barangay_staff', $barangay);

        $this->actingAs($staff)->patch("/staff/residents/{$otherStaff->id}", ['verified' => true])->assertNotFound();
    }

    public function test_reverifying_keeps_the_original_verifier(): void
    {
        $barangay = $this->barangay('A');
        $first = $this->userWithRole('barangay_staff', $barangay);
        $second = $this->userWithRole('barangay_staff', $barangay);
        $resident = $this->userWithRole('citizen', $barangay);

        $this->actingAs($first)->patch("/staff/residents/{$resident->id}", ['verified' => true]);
        $this->actingAs($second)->patch("/staff/residents/{$resident->id}", ['verified' => true]);

        $this->assertEquals($first->id, $resident->fresh()->resident_verified_by);
    }

    public function test_verification_can_be_removed_and_is_audited(): void
    {
        $barangay = $this->barangay('A');
        $staff = $this->userWithRole('barangay_staff', $barangay);
        $resident = $this->userWithRole('citizen', $barangay, ['resident_verified_at' => now()]);

        $this->actingAs($staff)->patch("/staff/residents/{$resident->id}", ['verified' => false]);

        $this->assertNull($resident->fresh()->resident_verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'resident_unverified', 'auditable_id' => $resident->id]);
    }

    public function test_verification_is_what_unlocks_voting(): void
    {
        $barangay = $this->barangay('A');
        $staff = $this->userWithRole('barangay_staff', $barangay);
        $resident = $this->userWithRole('citizen', $barangay);

        $poll = Poll::create([
            'title' => 'Test poll', 'is_published' => true,
            'opens_at' => now()->subHour(), 'closes_at' => now()->addHour(),
        ]);
        $option = $poll->options()->create(['label' => 'A', 'position' => 0]);

        $this->actingAs($resident)->post("/polls/{$poll->id}/vote", ['option_id' => $option->id])->assertForbidden();

        $this->actingAs($staff)->patch("/staff/residents/{$resident->id}", ['verified' => true]);

        $this->actingAs($resident->fresh())->post("/polls/{$poll->id}/vote", ['option_id' => $option->id])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, PollBallot::count());
    }
}