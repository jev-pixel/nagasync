<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReviewAccess;
use App\Models\DocumentRequest;
use App\Models\DocumentRequirement;
use App\Models\RequestAttachment;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Staff/admin review of the requirements attached to a request.
 * Approval itself goes through DocumentReviewController@update; the
 * DocumentRequest model blocks it until every required item is satisfied
 * (and, for City Hall documents, until the fee is assessed).
 */
class RequirementReviewController extends Controller
{
    private const OPEN = ['pending', 'processing', 'needs_resubmission'];

    public function show(Request $request, DocumentRequest $documentRequest)
    {
        $this->authorizeReview($request, $documentRequest);

        $documentRequest->load([
            'user:id,name',
            'barangay:id,name',
            'attachments.reviewer:id,name',
            'attachments.linkedDocument',
            'attachments.requirement',
        ]);

        $requirements = DocumentRequirement::active()
            ->where('document_type', $documentRequest->document_type)
            ->orderBy('sort_order')
            ->get();

        $byRequirement = $documentRequest->attachments->keyBy('document_requirement_id');

        $isCity     = $documentRequest->isCityLevel();
        $feeMissing = $isCity && ! $documentRequest->feeAssessed();

        return Inertia::render('Staff/Documents/Review', [
            'request' => $documentRequest->only([
                    'id', 'document_type', 'level', 'purpose', 'tracking_number', 'status', 'remarks', 'created_at',
                    'fee_total', 'fee_breakdown',
                ]) + [
                    'citizen'  => $documentRequest->user?->name,
                    'barangay' => $documentRequest->barangay?->name,
                ],
            'items' => $requirements->map(function ($req) use ($byRequirement, $documentRequest) {
                $a = $byRequirement->get($req->id);

                return [
                    'requirement_id' => $req->id,
                    'name'           => $req->name,
                    'description'    => $req->description,
                    'is_required'    => $req->is_required,
                    'kind'           => $req->kind,
                    'attachment'     => $a ? [
                        'id'            => $a->id,
                        'original_name' => $a->original_name,
                        'mime_type'     => $a->mime_type,
                        'review_status' => $a->review_status,
                        'remarks'       => $a->reviewer_remarks,
                        'reviewed_by'   => $a->reviewer?->name,
                        'reviewed_at'   => $a->reviewed_at,
                        'linked'        => $a->isLinked() ? [
                            'tracking_number' => $a->linkedDocument?->tracking_number,
                            'document_type'   => $a->linkedDocument?->document_type,
                            'status'          => $a->linkedDocument?->status,
                            'valid'           => $a->isStillValid($documentRequest->user_id),
                            'verify_url'      => $a->linkedDocument?->verificationUrl(),
                        ] : null,
                    ] : null,
                ];
            })->values(),
            'can_approve' => $documentRequest->requirementsComplete()
                && ! $feeMissing
                && in_array('approved', DocumentRequest::TRANSITIONS[$documentRequest->status] ?? [], true),
            'needs_fee'   => $feeMissing && in_array($documentRequest->status, self::OPEN, true),
            'missing'     => $documentRequest->missingRequirements()->pluck('name')->values(),
        ]);
    }

    public function verify(Request $request, RequestAttachment $attachment)
    {
        $doc = $attachment->documentRequest;
        $this->authorizeReview($request, $doc);
        $this->ensureReviewable($doc);

        $attachment->update([
            'review_status'    => 'verified',
            'reviewer_remarks' => null,
            'reviewed_by'      => $request->user()->id,
            'reviewed_at'      => now(),
        ]);

        $this->markProcessing($doc, $request);

        return back()->with('success', 'Document verified.');
    }

    public function reject(Request $request, RequestAttachment $attachment)
    {
        $doc = $attachment->documentRequest;
        $this->authorizeReview($request, $doc);
        $this->ensureReviewable($doc);

        $data = $request->validate([
            'remarks' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $attachment->update([
            'review_status'    => 'rejected',
            'reviewer_remarks' => $data['remarks'],
            'reviewed_by'      => $request->user()->id,
            'reviewed_at'      => now(),
        ]);

        $this->moveTo($doc, 'needs_resubmission', $request, 'A required document needs to be re-submitted.');

        // Hook your existing SMS-to-log notification here, e.g.:
        // app(SmsNotifier::class)->send($doc->user, "NagaSync {$doc->tracking_number}: please re-submit {$attachment->requirement->name}. {$data['remarks']}");

        return back()->with('success', 'Document rejected. The citizen has been asked to re-submit.');
    }

    /** ReviewAccess is level-aware: barangay staff cannot touch City Hall requests, and vice versa. */
    private function authorizeReview(Request $request, DocumentRequest $doc): void
    {
        abort_unless(ReviewAccess::canReview($request->user(), $doc), 403);
    }

    /** Finalised requests can no longer have their documents re-reviewed. */
    private function ensureReviewable(DocumentRequest $doc): void
    {
        if (! in_array($doc->status, self::OPEN, true)) {
            throw ValidationException::withMessages([
                'status' => 'This request is already finalised and can no longer be reviewed.',
            ]);
        }
    }

    private function markProcessing(DocumentRequest $doc, Request $request): void
    {
        if ($doc->status === 'pending') {
            $this->moveTo($doc, 'processing', $request);
        }
    }

    private function moveTo(DocumentRequest $doc, string $status, Request $request, ?string $remarks = null): void
    {
        if ($doc->status === $status) {
            return;
        }

        if (! in_array($status, DocumentRequest::TRANSITIONS[$doc->status] ?? [], true)) {
            return; // not a legal move from here; leave the status alone
        }

        $doc->update(array_filter([
            'status'       => $status,
            'processed_by' => $request->user()->id,
            'remarks'      => $remarks,
        ], fn ($v) => $v !== null));
    }
}