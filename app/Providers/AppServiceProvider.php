<?php

namespace App\Providers;

use App\Services\Notification\Drivers\SmsDriver;
use App\Services\Notification\Drivers\SMTPDriver;
use App\Services\Notification\Drivers\StubDriver;
use App\Services\Notification\NotificationService;
use App\Services\Notification\Providers\EmailProvider;
use App\Services\Notification\Providers\SmsProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NotificationService::class, function ($app) {
            $isProduction = $app->environment() === 'production';
            return (new NotificationService())
                ->addProvider(new SmsProvider(
                    !$isProduction
                        ? $app->make(StubDriver::class)
                        : $app->make(SmsDriver::class),
                ))
                ->addProvider(new EmailProvider(
                    !$isProduction
                        ? $app->make(StubDriver::class)
                        : $app->make(SMTPDriver::class),
                ));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
