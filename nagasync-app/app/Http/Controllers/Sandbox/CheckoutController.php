<?php

namespace App\Http\Controllers\Sandbox;

use App\Http\Controllers\Controller;
use App\Models\MunicipalPayment;
use App\Services\Payments\PaymentProcessor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function show(Request $request, MunicipalPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        if ($payment->status !== 'pending') {
            return $this->back($payment);
        }

        return Inertia::render('Payments/SandboxCheckout', [
            'reference' => $payment->reference_no,
            'label'     => $payment->meta['label'] ?? config("nagasync.payment_types.{$payment->payment_type}.label"),
            'total'     => $payment->total,
        ]);
    }

    public function complete(Request $request, MunicipalPayment $payment, PaymentProcessor $processor)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);
        $outcome = $request->validate(['outcome' => ['required', 'in:paid,failed']])['outcome'];

        $processor->settle($payment->reference_no, $outcome);

        return $this->back($payment)->with('success', 'Sandbox payment ' . $outcome . '.');
    }

    /** Document-fee payments go back to My Documents (residents cannot open the payments page). */
    private function back(MunicipalPayment $payment)
    {
        return redirect()->route($payment->payment_type === 'document_fee' ? 'documents.index' : 'payments.index');
    }
}