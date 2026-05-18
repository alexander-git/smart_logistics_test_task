<?php

namespace App\Providers;

use App\Services\DeduplicationRequest\DeduplicateRequestServiceInterface;
use App\Services\DeduplicationRequest\DeduplicationRequestService;
use App\Services\EmailSender\EmailSenderInterface;
use App\Services\EmailSender\FakeEmailSender;
use App\Services\SmsSender\FakeSmsSender;
use App\Services\SmsSender\SmsSenderInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmailSenderInterface::class, FakeEmailSender::class);
        $this->app->bind(SmsSenderInterface::class, FakeSmsSender::class);
        $this->app->bind(DeduplicateRequestServiceInterface::class, DeduplicationRequestService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('startNotification', function (Request $request) {
            return Limit::perMinute(100)
                ->by($request->ip());
        });

        RateLimiter::for('showReceiverHistory', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->ip());
        });
    }
}
