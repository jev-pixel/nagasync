<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReviewAccess;
use App\Models\DocumentRequest;
use App\Notifications\DocumentStatusChanged;
use App\Services\DocumentFeeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CounterController extends Controller
{
    public function __construct(private DocumentFeeCalculator $fees) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $doc = null;
        $error = null;

        if ($q !== '') {
            [$doc, $error] = $this->resolve($q);
        }

        return Inertia::render('Staff/Counter/Index', [
            'q'     => $q,
            'doc'   => $doc ? $this->payload($doc) : null,
            'error' => $error,
        ]);
    }

    public function release(Request $request, DocumentRequest $documentRequest)
    {
        // Each clerk releases only what their office handles (barangay staff: their barangay's documents)
        abort_unless(ReviewAccess::canReview($request->user(), $documentRequest), 403);

        // The receipt number and amount are checked below, once the fee is known: a free document needs neither
        $data = $request->validate([
            'official_receipt_no' => ['nullable', 'string', 'max:50'],
            'amount_paid'         => ['nullable', 'numeric', 'min:0'],
            'id_checked'          => ['accepted'],
        ]);

        DB::transaction(function () use ($documentRequest, $data, $request) {
            // Lock the row so two clerks (or a double click) cannot release the same request twice.
            $doc = DocumentRequest::whereKey($documentRequest->id)->lockForUpdate()->firstOrFail();

            if ($doc->status !== 'approved') {
                throw ValidationException::withMessages([
                    'official_receipt_no' => 'This request is not ready for release, or was already released.',
                ]);
            }

            $fee = $doc->fee_total !== null
                ? ['total' => (float) $doc->fee_total, 'breakdown' => $doc->fee_breakdown]
                : (function () use ($doc) { $f = $this->fees->compute($doc); return ['total' => $f['total'], 'breakdown' => $f]; })();

            $paid = (float) ($data['amount_paid'] ?? 0);

            if ($fee['total'] > 0 && blank($data['official_receipt_no'] ?? null)) {
                throw ValidationException::withMessages([
                    'official_receipt_no' => 'Enter the official receipt number for this payment.',
                ]);
            }

            if (abs($paid - $fee['total']) > 0.009) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Amount paid does not match the amount due.',
                ]);
            }

            $doc->update([
                'fee_total'           => $fee['total'],
                'fee_breakdown'       => $fee['breakdown'],
                'fee_computed_at'     => $doc->fee_computed_at ?? now(),
                'amount_paid'         => $paid,
                'official_receipt_no' => $data['official_receipt_no'] ?? null,
                'status'              => 'released',
                'released_at'         => now(),
                'released_by'         => $request->user()->id,
                // A city document is "issued" when handed over; this also starts its prerequisite age.
                'issued_at'           => now(),
            ]);

            // The Auditable trait on DocumentRequest logs this update.
        });

        $documentRequest->refresh()->user?->notify(new DocumentStatusChanged($documentRequest));

        return back()->with('success', 'Released. The document is now valid and can be verified by its QR code.');
    }

    // ---- helpers ---------------------------------------------------------

    /** Accepts a tracking number, or the full text a QR scanner types (the signed URL). */
    private function resolve(string $input): array
    {
        $doc = null;

        if (Str::startsWith($input, ['http://', 'https://'])) {
            if (! URL::hasValidSignature(\Illuminate\Http\Request::create($input))) {
                return [null, 'This QR code is invalid, expired or has been altered.'];
            }

            foreach (explode('/', (string) parse_url($input, PHP_URL_PATH)) as $segment) {
                if ($segment === '') {
                    continue;
                }
                $doc = DocumentRequest::where('tracking_number', $segment)->first()
                    ?? (ctype_digit($segment) ? DocumentRequest::find($segment) : null);
                if ($doc) {
                    break;
                }
            }
        } else {
            $doc = DocumentRequest::where('tracking_number', $input)->first();
        }

        if (! $doc) {
            return [null, 'No request matches that tracking number or QR code.'];
        }

        if (! ReviewAccess::canReview(request()->user(), $doc)) {
            return [null, 'This document is handled by another office.'];
        }

        return [$doc->load(['user.barangay', 'attachments.requirement', 'attachments.linkedDocument']), null];
    }

    private function payload(DocumentRequest $doc): array
    {
        $state = match (true) {
            $doc->status === 'released' => 'already_released',
            $doc->status === 'approved' => 'ready',
            $doc->status === 'rejected' => 'rejected',
            $doc->status === 'revoked'  => 'revoked',
            default                     => 'not_ready',
        };

        // Requests approved before fee assessment existed get a computed (not yet saved) fee.
        $fee = $doc->fee_total !== null
            ? ['total' => (float) $doc->fee_total, 'lines' => $doc->fee_breakdown['lines'] ?? []]
            : $this->fees->compute($doc);

        return [
            'id'              => $doc->id,
            'tracking_number' => $doc->tracking_number,
            'document_type'   => $doc->document_type,
            'purpose'         => $doc->purpose,
            'status'          => $doc->status,
            'state'           => $state,
            'citizen'         => ['name' => $doc->user->name, 'barangay' => $doc->user->barangay?->name],
            'requirements'    => $doc->attachments->map(fn ($a) => [
                'name'          => $a->requirement?->name,
                'review_status' => $a->review_status,
                'linked'        => $a->isLinked(),
                // Lets the clerk see, e.g., a cedula linked into a permit that is still unpaid.
                'linked_tracking' => $a->linkedDocument?->tracking_number,
                'linked_status'   => $a->linkedDocument?->status,
            ])->values(),
            'fee'             => ['total' => $fee['total'], 'lines' => $fee['lines']],
            'released_at'     => $doc->released_at?->toDayDateTimeString(),
            'receipt_no'      => $doc->official_receipt_no,
        ];
    }
}