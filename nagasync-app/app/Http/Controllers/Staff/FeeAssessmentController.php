<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReviewAccess;
use App\Models\DocumentRequest;
use App\Services\DocumentFeeCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * City Hall staff assess the fee BEFORE approving a city document.
 * DocumentRequest refuses to approve a city document until fee_total is set, so the citizen
 * and the clerk at the counter always see the same frozen amount.
 */
class FeeAssessmentController extends Controller
{
    private const ASSESSABLE = ['pending', 'processing', 'needs_resubmission'];
    private const ROLES      = ['city_staff', 'admin']; // barangay staff must not price City Hall documents

    public function store(Request $request, DocumentRequest $documentRequest, DocumentFeeCalculator $fees)
    {
        abort_unless(in_array($request->user()->role, self::ROLES, true), 403);
        abort_unless(ReviewAccess::canReview($request->user(), $documentRequest), 403); // office-scoped
        abort_unless($documentRequest->isCityLevel(), 404);
        abort_unless(
            in_array($documentRequest->status, self::ASSESSABLE, true),
            422,
            'The fee is fixed once a request is approved.'
        );

        $data = $request->validate([
            // Capital (new permit), gross sales (renewal) or annual income (community tax)
            'declared_amount'   => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            // Business permits: the barangay clearance fee is collected by the Treasurer with the permit
            'barangay_clearance_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            // Optional staff override, always with a reason (the Auditable trait logs the change)
            'adjustment'        => ['nullable', 'numeric', 'between:-1000000,1000000'],
            'adjustment_reason' => ['nullable', 'required_with:adjustment', 'string', 'max:255'],
        ]);

        $fee = $fees->compute($documentRequest, [
            'declared_amount'        => $data['declared_amount'] ?? 0,
            'barangay_clearance_fee' => $data['barangay_clearance_fee'] ?? 0,
        ]);

        if (! empty($data['adjustment'])) {
            $fee['lines'][] = [
                'label'  => 'Adjustment: ' . $data['adjustment_reason'],
                'amount' => round((float) $data['adjustment'], 2),
            ];
            $fee['total'] = round(array_sum(array_column($fee['lines'], 'amount')), 2);
        }

        if ($fee['total'] < 0) {
            throw ValidationException::withMessages([
                'adjustment' => 'The adjustment cannot make the total negative.',
            ]);
        }

        // Status is unchanged here, so the approval gate in the model is not triggered.
        $documentRequest->update([
            'fee_total'       => $fee['total'],
            'fee_breakdown'   => $fee,
            'fee_computed_at' => now(),
        ]);

        return back()->with('success', 'Fee assessed: PHP ' . number_format($fee['total'], 2) . '. You can now approve the request.');
    }
}