<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureSuperadmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveCurrentOrganization;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Laravel 11+ doesn't wire this in by default — see
        // AppServiceProvider::boot() for the 'api' limiter definition.
        $middleware->api(prepend: [
            ThrottleRequests::class.':api',
        ]);

        $middleware->alias([
            'current-org' => ResolveCurrentOrganization::class,
            // Overrides Laravel's own 'verified' alias — see the class for why.
            'verified' => EnsureEmailIsVerified::class,
            'superadmin' => EnsureSuperadmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Report uncaught exceptions to Sentry. Inert unless
        // SENTRY_LARAVEL_DSN is set, so local and CI stay offline.
        Integration::handles($exceptions);
    })->create();
