<?php

namespace App\Services\Payments;

use App\Models\MunicipalPayment;
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

        return $payment;
    }
}