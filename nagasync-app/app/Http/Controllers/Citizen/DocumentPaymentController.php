<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\MunicipalPayment;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * A resident pays the fee of an APPROVED document online instead of at the counter.
 * The amount always comes from the frozen document fee (fee_total, pesos), never from the client.
 * When the payment settles, PaymentProcessor releases the document.
 */
class DocumentPaymentController extends Controller
{
    public function store(Request $request, DocumentRequest $documentRequest, PaymentGateway $gateway)
    {
        abort_unless($documentRequest->user_id === $request->user()->id, 403);

        $payment = DB::transaction(function () use ($documentRequest, $request) {
            // Lock the request so a double click cannot create two payments for it
            $doc = DocumentRequest::whereKey($documentRequest->id)->lockForUpdate()->firstOrFail();

            abort_unless($doc->status === 'approved', 422, 'This document is not waiting for payment.');
            abort_unless($doc->requiresPayment(), 422, 'This document is free. No payment is needed.');

            $existing = $doc->payment_id ? MunicipalPayment::find($doc->payment_id) : null;

            if ($existing?->status === 'pending') {
                return $existing; // continue the checkout the resident already started
            }
            abort_if($existing?->status === 'paid', 422, 'This document has already been paid.');

            $amount = (int) round((float) $doc->fee_total * 100); // pesos -> centavos
            $fee    = (int) config('nagasync.convenience_fee');

            $payment = MunicipalPayment::create([
                'reference_no'    => MunicipalPayment::generateReference(),
                'user_id'         => $request->user()->id,
                'barangay_id'     => $request->user()->barangay_id,
                'payment_type'    => 'document_fee',
                'amount'          => $amount,
                'convenience_fee' => $fee,
                'total'           => $amount + $fee,
                'gateway'         => config('nagasync.payments.gateway'),
                'meta'            => [
                    'document_request_id' => $doc->id,
                    'label'               => "{$doc->document_type} ({$doc->tracking_number})",
                ],
            ]);

            $doc->update(['payment_id' => $payment->id]);

            return $payment;
        });

        $checkout = $gateway->createCheckout($payment);
        $payment->update(['gateway_ref' => $checkout['gateway_ref']]);

        return Inertia::location($checkout['url']);
    }
}
