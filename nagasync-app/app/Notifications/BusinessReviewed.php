<?php

namespace App\Notifications;

use App\Models\Business;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BusinessReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [30, 120];

    public function __construct(public Business $business)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        $b = $this->business;
        $reason = $b->review_note ?: 'see the portal';

        return match ($b->status) {
            'approved'  => "NagaSync: Your listing \"{$b->name}\" is now live in the directory.",
            'rejected'  => "NagaSync: Your listing \"{$b->name}\" was not approved. Reason: {$reason}.",
            'suspended' => "NagaSync: Your listing \"{$b->name}\" has been suspended. Reason: {$reason}.",
            default     => "NagaSync: Your listing \"{$b->name}\" is now {$b->status}.",
        };
    }
}