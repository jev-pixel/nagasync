<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReviewAccess;
use App\Models\DocumentRequest;
use App\Notifications\DocumentStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DocumentReviewController extends Controller
{
    // Statuses staff can set from this queue. "released" is intentionally absent: it is done at the
    // City Hall counter (payment + hand-over). "pending" after needs_resubmission is the resident's action.
    private const QUEUE_STATUSES = ['processing', 'approved', 'rejected', 'revoked', 'needs_resubmission'];

    // Statuses that trigger a notification to the resident.
    private const NOTIFY_STATUSES = ['approved', 'released', 'rejected', 'revoked', 'needs_resubmission'];

    public function index(Request $request)
    {
        $user = $request->user();

        // ReviewAccess decides what each role may see: admin everything, city_staff City Hall
        // requests from any barangay, barangay_staff their own barangay's barangay-level requests.
        $q = ReviewAccess::scopeQueue(
            $user,
            DocumentRequest::with(['user:id,name', 'barangay:id,name'])->latest()
        );

        return Inertia::render('Staff/Documents/Index', [
            'documents' => $q->paginate(20)->through(fn ($d) => [
                'id' => $d->id, 'tracking_number' => $d->tracking_number,
                'document_type' => $d->document_type, 'purpose' => $d->purpose,
                'level' => $d->level, 'fee_total' => $d->fee_total,
                'status' => $d->status, 'remarks' => $d->remarks,
                'citizen' => $d->user?->name, 'barangay' => $d->barangay?->name,
                'created_at' => $d->created_at->toDateTimeString(),
            ]),
            // Only send transitions this screen can actually perform, so no button leads to a 422.
            'transitions' => collect(DocumentRequest::TRANSITIONS)
                ->map(fn ($to) => array_values(array_intersect($to, self::QUEUE_STATUSES)))
                ->all(),
        ]);
    }

    public function update(Request $request, DocumentRequest $documentRequest)
    {
        $user = $request->user();

        abort_unless(ReviewAccess::canReview($user, $documentRequest), 403);

        $data = $request->validate([
            'status'  => ['required', Rule::in(self::QUEUE_STATUSES)],
            'remarks' => [
                Rule::requiredIf(fn () => in_array($request->input('status'), ['rejected', 'revoked', 'needs_resubmission'], true)),
                'nullable', 'string', 'max:500',
            ],
        ]);

        // Lock the row so two simultaneous clicks can't both pass the transition check.
        // The DocumentRequest model also refuses to approve a request whose requirements are
        // unverified, or a City Hall request whose fee has not been assessed.
        $document = DB::transaction(function () use ($documentRequest, $data, $user) {
            $doc = DocumentRequest::whereKey($documentRequest->id)->lockForUpdate()->firstOrFail();

            abort_unless(
                in_array($data['status'], DocumentRequest::TRANSITIONS[$doc->status] ?? [], true),
                422,
                'Invalid status change.'
            );

            $approving = $data['status'] === 'approved';

            $doc->update([
                'status'         => $data['status'],
                'remarks'        => $data['remarks'] ?? $doc->remarks,
                'processed_by'   => $user->id,
                'issued_at'      => $approving ? now() : $doc->issued_at,
                // Remember who signed, so a reissued document keeps the original signatory
                'signatory_name' => $approving && ! $doc->isCityLevel()
                    ? ($doc->barangay?->captain_name ?? $doc->signatory_name)
                    : $doc->signatory_name,
            ]);

            // A free barangay document needs no payment: it is issued the moment it is approved.
            if ($approving && ! $doc->isCityLevel() && ! $doc->requiresPayment()) {
                $doc->update([
                    'status'      => 'released',
                    'amount_paid' => 0,
                    'released_at' => now(),
                    'released_by' => $user->id,
                ]);
            }

            return $doc;
        });

        if (in_array($data['status'], self::NOTIFY_STATUSES, true)) {
            $document->user?->notify(new DocumentStatusChanged($document));
        }

        return back()->with('success', 'Status updated.');
    }
}