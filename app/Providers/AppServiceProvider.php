<?php

namespace App\Providers;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event) {
            ActivityLogger::log('user.login', 'user', $event->user->id, $event->user->email,
                "User {$event->user->name} logged in", ['user_agent' => request()->userAgent()]);
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                ActivityLogger::log('user.logout', 'user', $event->user->id, $event->user->email,
                    "User {$event->user->name} logged out");
            }
        });

        Event::listen(Failed::class, function (Failed $event) {
            $email = $event->credentials['email'] ?? 'unknown';
            ActivityLogger::log('user.login_failed', null, null, $email,
                "Failed login attempt for {$email}");
        });
    }
}
