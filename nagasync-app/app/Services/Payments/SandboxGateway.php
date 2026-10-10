<?php

namespace App\Services\Payments;

use App\Models\MunicipalPayment;
use Illuminate\Support\Str;

class SandboxGateway implements PaymentGateway
{
    public function __construct()
    {
        abort_if(app()->isProduction(), 500, 'Sandbox gateway is disabled in production.');
    }

    public function createCheckout(MunicipalPayment $payment): array
    {
        return [
            'url'         => route('payments.sandbox', $payment->reference_no),
            'gateway_ref' => 'sbx_' . Str::lower(Str::random(12)),
        ];
    }
}