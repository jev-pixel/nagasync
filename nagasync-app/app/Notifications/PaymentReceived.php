<?php

namespace App\Notifications;

use App\Models\MunicipalPayment;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public MunicipalPayment $payment)
{
    $this->afterCommit();
}

    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        $amount = number_format($this->payment->total / 100, 2);

        return "NagaSync: Payment of PHP {$amount} received. Ref {$this->payment->reference_no}. Thank you.";
    }
}