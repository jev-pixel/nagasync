<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsSender implements SmsSender
{
    public function __construct()
    {
        abort_if(app()->isProduction(), 500, 'Log SMS driver is disabled in production.');
    }

    public function send(string $to, string $message): void
    {
        Log::info("[SMS to {$to}] {$message}");
    }
}