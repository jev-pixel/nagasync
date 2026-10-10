<?php

namespace App\Notifications;

use App\Models\DocumentRequest;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DocumentStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DocumentRequest $document)
{
    $this->afterCommit();
}

    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        $d = $this->document;
        $ref = "{$d->document_type} ({$d->tracking_number})";

        return match ($d->status) {
            'approved' => "NagaSync: Your {$ref} is approved. View it under My Documents.",
            'rejected' => "NagaSync: Your {$ref} was not approved. Reason: " . ($d->remarks ?: 'see the portal') . '.',
            'revoked'  => "NagaSync: Your {$ref} has been revoked. Reason: " . ($d->remarks ?: 'see the portal') . '.',
            default    => "NagaSync: Your {$ref} is now {$d->status}.",
        };
    }
}