<?php

namespace Tests\Feature;

use App\Models\DocumentRequest;
use App\Models\User;
use App\Notifications\DocumentStatusChanged;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentFlowTest extends TestCase
{
    private function makeDocument(User $owner, string $status = 'pending'): DocumentRequest
    {
        return DocumentRequest::create([
            'user_id' => $owner->id, 'barangay_id' => $owner->barangay_id,
            'document_type' => 'Barangay Clearance', 'purpose' => 'employment',
            'tracking_number' => DocumentRequest::generateTrackingNumber(), 'status' => $status,
        ]);
    }

    public function test_citizen_can_request_a_document(): void
    {
        $citizen = $this->userWithRole('citizen');

        $this->actingAs($citizen)->post('/documents', ['document_type' => 'Barangay Clearance', 'purpose' => 'employment']);

        $this->assertDatabaseHas('document_requests', ['user_id' => $citizen->id, 'status' => 'pending']);
    }

    public function test_citizen_without_a_barangay_is_rejected(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen', 'barangay_id' => null]);

        $this->actingAs($citizen)
            ->post('/documents', ['document_type' => 'Barangay Clearance', 'purpose' => 'employment'])
            ->assertSessionHasErrors('document_type');
    }

    public function test_invalid_status_change_is_refused(): void
    {
        $staff = $this->userWithRole('barangay_staff');
        $doc = $this->makeDocument($this->userWithRole('citizen'), 'rejected');

        $this->actingAs($staff)->patch("/staff/documents/{$doc->id}", ['status' => 'approved'])->assertStatus(422);
        $this->assertSame('rejected', $doc->fresh()->status);
    }

    public function test_staff_of_another_barangay_cannot_act(): void
    {
        $doc = $this->makeDocument($this->userWithRole('citizen', $this->barangay('A')));
        $outsider = $this->userWithRole('barangay_staff', $this->barangay('B'));

        $this->actingAs($outsider)->patch("/staff/documents/{$doc->id}", ['status' => 'approved'])->assertForbidden();
    }

    public function test_staff_without_a_barangay_cannot_act_on_unassigned_documents(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen', 'barangay_id' => null]);
        $doc = $this->makeDocument($citizen);
        $staff = User::factory()->create(['role' => 'barangay_staff', 'barangay_id' => null]);

        $this->actingAs($staff)->patch("/staff/documents/{$doc->id}", ['status' => 'approved'])->assertForbidden();
    }

    public function test_approval_notifies_the_citizen(): void
    {
        Notification::fake();
        $citizen = $this->userWithRole('citizen');
        $staff = $this->userWithRole('barangay_staff');
        $doc = $this->makeDocument($citizen);

        $this->actingAs($staff)->patch("/staff/documents/{$doc->id}", ['status' => 'approved'])->assertRedirect();

        Notification::assertSentTo($citizen, DocumentStatusChanged::class);
    }

    public function test_verification_link_is_signed_and_reflects_live_status(): void
    {
        $doc = $this->makeDocument($this->userWithRole('citizen'), 'approved');
        $url = $doc->verificationUrl();

        $this->get($url)->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Verify')->where('valid', true));

        $this->get($url . '0')->assertForbidden(); // tampered signature

        $doc->update(['status' => 'revoked']);
        $this->get($url)->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('valid', false));
    }
}