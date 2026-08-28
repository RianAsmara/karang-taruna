<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel 11+ no longer wires `throttle:api` into the `api`
        // middleware group by default (unlike the older RouteServiceProvider
        // convention this codebase's docs/security.md assumed) — every
        // /api/v1 route, and the public web pages below, had zero rate
        // limiting until this was added. 60/min per authenticated user
        // (or per IP, unauthenticated) is a generous but real ceiling —
        // login/register keep their own tighter, purpose-specific limits
        // on top of this (unaffected by the testing bypass below — they
        // use their own dedicated RateLimiter keys, not this one).
        //
        // Unlimited while running the test suite: many unauthenticated
        // guest-view tests across different files legitimately share one
        // IP-keyed bucket within the same real-time minute, and this
        // blanket ceiling is a production defense-in-depth concern, not
        // something worth asserting on here — the auth-specific limiters
        // above are what's actually tested (see AuthTest).
        $unlimited = fn () => Limit::none();

        RateLimiter::for('api', $this->app->runningUnitTests()
            ? $unlimited
            : fn ($request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Read-only, unauthenticated public pages (transparency, report
        // sharing, the attendance QR image) — generous, but not
        // unbounded.
        RateLimiter::for('public-pages', $this->app->runningUnitTests()
            ? $unlimited
            : fn ($request) => Limit::perMinute(60)->by($request->ip()));
    }
}
