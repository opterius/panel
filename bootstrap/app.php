<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'api_key' => \App\Http\Middleware\ApiKeyAuth::class,
            'reseller_acl' => \App\Http\Middleware\ResellerAcl::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetupMiddleware::class,
            \App\Http\Middleware\LicenseMiddleware::class,
            \App\Http\Middleware\SetLocale::class,
        ]);

        // Exclude the agent → panel callback URLs from CSRF (auth is via
        // shared token in the request body).
        $middleware->validateCsrfTokens(except: [
            'api/cron/report',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
