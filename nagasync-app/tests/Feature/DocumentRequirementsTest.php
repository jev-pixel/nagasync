<?php

namespace Tests\Feature;

use App\Models\DocumentRequest;
use App\Models\DocumentRequirement;
use App\Models\User;
use Database\Seeders\DocumentRequirementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * ADAPT the helpers at the bottom (citizen() / staff() / cityStaff()) to your
 * User factory and role setup. Everything else should run as written.
 */
class DocumentRequirementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(DocumentRequirementSeeder::class);
    }

    // ===================== Barangay level =====================

    public function test_cannot_submit_without_required_files(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)
            ->post(route('documents.store'), [
                'document_type' => 'Barangay Clearance',
                'purpose'       => 'Employment',
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('document_requests', 0);
    }

    public function test_can_submit_with_all_required_files(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)
            ->post(route('documents.store'), $this->payload($citizen, 'Barangay Clearance'))
            ->assertSessionHasNoErrors();

        $doc = DocumentRequest::firstOrFail();
        $required = DocumentRequirement::where('document_type', 'Barangay Clearance')->required()->count();

        $this->assertSame($required, $doc->attachments()->count());
        $this->assertSame('pending', $doc->status);
        $this->assertSame('barangay', $doc->level);
    }

    public function test_approval_is_blocked_until_all_required_items_are_verified(): void
    {
        $doc = $this->submittedRequest();

        $this->expectException(ValidationException::class);
        $doc->update(['status' => 'approved']);
    }

    public function test_approval_succeeds_once_everything_is_verified(): void
    {
        $doc = $this->submittedRequest();
        $doc->attachments()->update(['review_status' => 'verified']);

        $doc->update(['status' => 'approved']);

        $this->assertSame('approved', $doc->fresh()->status);
    }

    public function test_citizen_cannot_download_another_citizens_attachment(): void
    {
        $doc = $this->submittedRequest();
        $attachment = $doc->attachments()->first();

        $this->actingAs($this->citizen())
            ->get(route('attachments.show', $attachment))
            ->assertForbidden();

        $this->actingAs($doc->user)
            ->get(route('attachments.show', $attachment))
            ->assertOk();
    }

    public function test_rejecting_requires_remarks_and_flags_resubmission(): void
    {
        $doc = $this->submittedRequest();
        $attachment = $doc->attachments()->first();
        $staff = $this->staff($doc->barangay_id);

        $this->actingAs($staff)
            ->post(route('staff.attachments.reject', $attachment), ['remarks' => ''])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($staff)
            ->post(route('staff.attachments.reject', $attachment), ['remarks' => 'Photo is blurry, please retake.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('rejected', $attachment->fresh()->review_status);
        $this->assertSame('needs_resubmission', $doc->fresh()->status);
    }

    public function test_citizen_can_resubmit_a_rejected_item_and_returns_to_pending(): void
    {
        $doc = $this->submittedRequest();
        $attachment = $doc->attachments()->first();
        $this->actingAs($this->staff($doc->barangay_id))
            ->post(route('staff.attachments.reject', $attachment), ['remarks' => 'Photo is blurry, please retake.']);

        $this->actingAs($doc->user)
            ->post(route('documents.resubmit', [$doc, $attachment->document_requirement_id]), [
                'file' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('pending', $attachment->fresh()->review_status);
        $this->assertSame('pending', $doc->fresh()->status);
    }

    public function test_staff_from_another_barangay_cannot_review(): void
    {
        $doc = $this->submittedRequest();
        $attachment = $doc->attachments()->first();

        $this->actingAs($this->staff($doc->barangay_id + 999))
            ->post(route('staff.attachments.verify', $attachment))
            ->assertForbidden();
    }

    // ===================== City Hall level =====================

    public function test_city_request_can_use_approved_barangay_documents_as_requirements(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)
            ->post(route('documents.store'), $this->payload($citizen, 'Business Permit (New)'))
            ->assertSessionHasNoErrors();

        $city = DocumentRequest::where('document_type', 'Business Permit (New)')->firstOrFail();

        $this->assertSame('city', $city->level);
        $linked = $city->attachments()->whereNotNull('linked_document_request_id')->get();
        $this->assertGreaterThan(0, $linked->count());
        $this->assertTrue($linked->every(fn ($a) => $a->review_status === 'verified'));
    }

    public function test_city_request_is_blocked_when_a_prerequisite_is_missing(): void
    {
        $citizen = $this->citizen();
        $payload = $this->payload($citizen, 'Business Permit (New)');

        // Drop the Business Clearance link and upload nothing in its place
        $req = DocumentRequirement::where('document_type', 'Business Permit (New)')
            ->where('satisfied_by_type', 'Business Clearance')->firstOrFail();
        unset($payload['links'][$req->id]);

        $this->actingAs($citizen)
            ->post(route('documents.store'), $payload)
            ->assertSessionHasErrors("files.{$req->id}");

        $this->assertDatabaseMissing('document_requests', ['document_type' => 'Business Permit (New)']);
    }

    public function test_city_request_can_use_an_uploaded_copy_instead_of_a_link(): void
    {
        $citizen = $this->citizen();
        $payload = $this->payload($citizen, 'Business Permit (New)');

        $req = DocumentRequirement::where('document_type', 'Business Permit (New)')
            ->where('satisfied_by_type', 'Business Clearance')->firstOrFail();
        unset($payload['links'][$req->id]);
        $payload['files'][$req->id] = UploadedFile::fake()->image('paper-clearance.jpg');

        $this->actingAs($citizen)
            ->post(route('documents.store'), $payload)
            ->assertSessionHasNoErrors();

        $attachment = DocumentRequest::where('document_type', 'Business Permit (New)')->firstOrFail()
            ->attachments()->where('document_requirement_id', $req->id)->firstOrFail();

        $this->assertNull($attachment->linked_document_request_id);
        $this->assertSame('pending', $attachment->review_status); // staff must verify a paper copy
    }

    public function test_cannot_link_another_residents_document(): void
    {
        $citizen = $this->citizen();
        $payload = $this->payload($citizen, 'Business Permit (New)');

        $req = DocumentRequirement::where('document_type', 'Business Permit (New)')
            ->where('satisfied_by_type', 'Business Clearance')->firstOrFail();
        $someoneElses = $this->issuedDoc($this->citizen(), 'Business Clearance');
        $payload['links'][$req->id] = $someoneElses->id;

        $this->actingAs($citizen)
            ->post(route('documents.store'), $payload)
            ->assertSessionHasErrors("files.{$req->id}");
    }

    public function test_cannot_link_a_document_older_than_the_allowed_age(): void
    {
        $citizen = $this->citizen();
        $payload = $this->payload($citizen, 'Business Permit (New)');

        $req = DocumentRequirement::where('document_type', 'Business Permit (New)')
            ->where('satisfied_by_type', 'Business Clearance')->firstOrFail();
        $old = $this->issuedDoc($citizen, 'Business Clearance', now()->subDays(($req->max_age_days ?? 180) + 30));
        $payload['links'][$req->id] = $old->id;

        $this->actingAs($citizen)
            ->post(route('documents.store'), $payload)
            ->assertSessionHasErrors("files.{$req->id}");
    }

    public function test_revoking_a_linked_document_blocks_city_approval(): void
    {
        $citizen = $this->citizen();
        $this->actingAs($citizen)->post(route('documents.store'), $this->payload($citizen, 'Business Permit (New)'));

        $city = DocumentRequest::where('document_type', 'Business Permit (New)')->firstOrFail();
        $city->attachments()->whereNull('linked_document_request_id')->update(['review_status' => 'verified']);
        $this->assertTrue($city->fresh()->requirementsComplete());

        // The barangay later revokes the Business Clearance that was linked
        $linkedId = $city->attachments()->whereNotNull('linked_document_request_id')
            ->whereHas('requirement', fn ($q) => $q->where('satisfied_by_type', 'Business Clearance'))
            ->firstOrFail()->linked_document_request_id;
        DocumentRequest::findOrFail($linkedId)->update(['status' => 'revoked']);

        $this->assertFalse($city->fresh()->requirementsComplete());

        $this->expectException(ValidationException::class);
        $city->fresh()->update(['status' => 'approved']);
    }

    public function test_citizen_can_relink_after_the_linked_document_becomes_invalid(): void
    {
        $citizen = $this->citizen();
        $this->actingAs($citizen)->post(route('documents.store'), $this->payload($citizen, 'Business Permit (New)'));

        $city = DocumentRequest::where('document_type', 'Business Permit (New)')->firstOrFail();
        $req = DocumentRequirement::where('document_type', 'Business Permit (New)')
            ->where('satisfied_by_type', 'Business Clearance')->firstOrFail();

        $oldId = $city->attachments()->where('document_requirement_id', $req->id)->firstOrFail()->linked_document_request_id;
        DocumentRequest::findOrFail($oldId)->update(['status' => 'revoked']);
        $fresh = $this->issuedDoc($citizen, 'Business Clearance');

        $this->actingAs($citizen)
            ->post(route('documents.link', [$city, $req]), ['document_id' => $fresh->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($fresh->id, $city->attachments()->where('document_requirement_id', $req->id)->first()->linked_document_request_id);
    }

    public function test_barangay_staff_cannot_review_city_requests(): void
    {
        $citizen = $this->citizen();
        $this->actingAs($citizen)->post(route('documents.store'), $this->payload($citizen, 'Business Permit (New)'));

        $city = DocumentRequest::where('document_type', 'Business Permit (New)')->firstOrFail();
        $attachment = $city->attachments()->whereNull('linked_document_request_id')->firstOrFail();

        $this->actingAs($this->staff($city->barangay_id))
            ->post(route('staff.attachments.verify', $attachment))
            ->assertForbidden();

        $this->actingAs($this->cityStaff())
            ->post(route('staff.attachments.verify', $attachment))
            ->assertSessionHasNoErrors();

        $this->assertSame('verified', $attachment->fresh()->review_status);
    }

    // ---- helpers ---------------------------------------------------------

    /**
     * Valid submission data for $type. Barangay requirements get fake uploads.
     * "Issued document" requirements get a link to a freshly issued approved document.
     */
    private function payload(User $user, string $type): array
    {
        $files = [];
        $links = [];

        foreach (DocumentRequirement::where('document_type', $type)->required()->get() as $req) {
            if ($req->isIssuedKind()) {
                $links[$req->id] = $this->issuedDoc($user, $req->satisfied_by_type)->id;
            } else {
                $files[$req->id] = UploadedFile::fake()->image("req-{$req->id}.jpg");
            }
        }

        return ['document_type' => $type, 'purpose' => 'Employment', 'files' => $files, 'links' => $links];
    }

    /** An already-approved document of $type owned by $user (what a resident would link). */
    private function issuedDoc(User $user, string $type, $issuedAt = null): DocumentRequest
    {
        return DocumentRequest::create([
            'user_id'         => $user->id,
            'barangay_id'     => $user->barangay_id,
            'document_type'   => $type,
            'purpose'         => 'Test',
            'tracking_number' => DocumentRequest::generateTrackingNumber(),
            'status'          => 'approved',
            'issued_at'       => $issuedAt ?? now(),
        ]);
    }

    private function submittedRequest(): DocumentRequest
    {
        $citizen = $this->citizen();
        $this->actingAs($citizen)->post(route('documents.store'), $this->payload($citizen, 'Barangay Clearance'));

        return DocumentRequest::with('user')->where('document_type', 'Barangay Clearance')->firstOrFail();
    }

    /** Adapt to your User factory: needs a barangay_id and the citizen role. */
    private function citizen(): User
    {
        return User::factory()->create(['barangay_id' => 1 /* , 'role' => 'citizen' */]);
    }

    /** Barangay staff belonging to $barangayId. */
    private function staff(int $barangayId): User
    {
        return User::factory()->create(['barangay_id' => $barangayId, 'role' => 'barangay_staff']);
    }

    /** City Hall clerk. Requires the 'city_staff' role to exist in your users.role column. */
    private function cityStaff(): User
    {
        return User::factory()->create(['role' => 'city_staff']);
    }
}
