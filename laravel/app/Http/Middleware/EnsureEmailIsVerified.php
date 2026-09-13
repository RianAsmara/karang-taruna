<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replaces Laravel's own 'verified' middleware for two reasons.
 *
 * 1. It redirects with `Redirect::guest()`, which stores the requested URL
 *    as the session's intended destination. The framework's version does
 *    not, so an unverified user who opens an invite link would verify and
 *    then land on the dashboard having never joined — the same silent
 *    onboarding failure as W-002, one step further along the flow.
 *    `VerifyEmailController` already redirects to `intended()`, so storing
 *    it here is all that flow needs.
 *
 * 2. It answers API clients with JSON in Indonesian rather than an HTML
 *    redirect. Mobile has no verification screen of its own; the message
 *    is what the user will actually read, so it has to say what to do.
 */
class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Email Anda belum diverifikasi. Buka tautan verifikasi yang kami kirim ke '.$user->email.', lalu coba lagi.',
                    'code' => 'email_unverified',
                ], 403);
            }

            return Redirect::guest(route('verification.notice'));
        }

        return $next($request);
    }
}
