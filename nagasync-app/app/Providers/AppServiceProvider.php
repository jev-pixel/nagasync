<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
    \App\Services\Payments\PaymentGateway::class,
    fn () => match (config('nagasync.payments.gateway')) {
        'sandbox' => new \App\Services\Payments\SandboxGateway(),
        default   => throw new \RuntimeException('Unknown payment gateway configured.'),
    }
);
$this->app->bind(
    \App\Services\Sms\SmsSender::class,
    fn () => match (config('nagasync.sms.driver')) {
        'log'   => new \App\Services\Sms\LogSmsSender(),
        default => throw new \RuntimeException('Unknown SMS driver configured.'),
    }
);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
