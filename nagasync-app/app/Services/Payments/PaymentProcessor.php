<?php

namespace App\Services\Payments;

use App\Models\DocumentRequest;
use App\Models\MunicipalPayment;
use App\Notifications\DocumentStatusChanged;
use App\Notifications\PaymentReceived;
use Illuminate\Support\Facades\DB;

class PaymentProcessor
{
    /** Idempotent: safe to call repeatedly for the same reference. */
    public function settle(string $referenceNo, string $outcome, ?string $gatewayRef = null): MunicipalPayment
    {
        [$payment, $changed] = DB::transaction(function () use ($referenceNo, $outcome, $gatewayRef) {
            $payment = MunicipalPayment::where('reference_no', $referenceNo)->lockForUpdate()->firstOrFail();

            if ($payment->status !== 'pending') {
                return [$payment, false]; // already settled: ignore the duplicate
            }

            $paid = $outcome === 'paid';
            $payment->update([
                'status'      => $paid ? 'paid' : 'failed',
                'paid_at'     => $paid ? now() : null,
                'gateway_ref' => $gatewayRef ?? $payment->gateway_ref,
            ]);

            return [$payment, true];
        });

        // After commit, and only on the real transition. A notification problem
        // must never undo or mask a recorded payment.
        if ($changed && $payment->status === 'paid') {
            try {
                $payment->user->notify(new PaymentReceived($payment));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // A paid document fee releases the document. Runs for every settle of a paid payment, not only
        // the first, so a repeated webhook heals a release that failed earlier. It is idempotent.
        if ($payment->status === 'paid') {
            try {
                $document = $this->releaseDocumentFor($payment);
                $document?->user?->notify(new DocumentStatusChanged($document));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $payment;
    }

    /** Release the approved document this payment was made for. Returns it only when released just now. */
    private function releaseDocumentFor(MunicipalPayment $payment): ?DocumentRequest
    {
        return DB::transaction(function () use ($payment) {
            $doc = DocumentRequest::where('payment_id', $payment->id)->lockForUpdate()->first();

            if (! $doc || $doc->status === 'released') {
                return null; // not a document payment, or already released by this payment
            }

            if ($doc->status !== 'approved') {
                // Paid after the clerk released it at the counter, or after it was revoked: needs a manual refund
                $payment->update(['meta' => array_merge($payment->meta ?? [], ['needs_refund' => true])]);
                report(new \RuntimeException("Payment {$payment->reference_no} settled for document {$doc->tracking_number} in status {$doc->status}."));

                return null;
            }

            $doc->update([
                'status'              => 'released',
                'amount_paid'         => $doc->fee_total,
                'official_receipt_no' => $payment->reference_no, // the online reference acts as the receipt
                'released_at'         => now(),
                // A city document is "issued" when handed over; a barangay one keeps its approval date
                'issued_at'           => $doc->isCityLevel() ? now() : ($doc->issued_at ?? now()),
            ]);

            return $doc;
        });
    }
}