<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\MunicipalPayment;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $types = config('nagasync.payment_types');

        return Inertia::render('Payments/Index', [
            'types' => collect($types)->map(fn ($t, $k) => ['key' => $k] + $t)->values(),
            'fee'   => config('nagasync.convenience_fee'),
            'payments' => MunicipalPayment::where('user_id', $request->user()->id)->latest()->get()
                ->map(fn ($p) => [
                    'id' => $p->id, 'reference_no' => $p->reference_no,
                    'label' => $types[$p->payment_type]['label'] ?? $p->payment_type,
                    'total' => $p->total, 'status' => $p->status,
                    'paid_at' => $p->paid_at?->toDayDateTimeString(),
                ]),
        ]);
    }

    public function store(Request $request, PaymentGateway $gateway)
    {
        $data = $request->validate([
            'payment_type' => ['required', Rule::in(array_keys(config('nagasync.payment_types')))],
        ]);

        // The amount always comes from the server-side schedule, never from the client
        $amount = config("nagasync.payment_types.{$data['payment_type']}.amount");
        $fee = config('nagasync.convenience_fee');

        $payment = MunicipalPayment::create([
            'reference_no'    => MunicipalPayment::generateReference(),
            'user_id'         => $request->user()->id,
            'barangay_id'     => $request->user()->barangay_id,
            'payment_type'    => $data['payment_type'],
            'amount'          => $amount,
            'convenience_fee' => $fee,
            'total'           => $amount + $fee,
            'gateway'         => config('nagasync.payments.gateway'),
        ]);

        $checkout = $gateway->createCheckout($payment);
        $payment->update(['gateway_ref' => $checkout['gateway_ref']]);

        return Inertia::location($checkout['url']); // full-page redirect, works for external gateways too
    }
}