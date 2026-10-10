<?php

namespace App\Services\Payments;

use App\Models\MunicipalPayment;

interface PaymentGateway
{
    /** @return array{url: string, gateway_ref: string} */
    public function createCheckout(MunicipalPayment $payment): array;
}