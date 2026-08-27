<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Resources\PersonalAccessTokenResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Web equivalent of the API's session management
 * (App\Http\Controllers\Api\V1\AuthController::sessions/destroySession)
 * — same underlying `tokens()` relation, safe to reuse verbatim since it
 * doesn't depend on which guard authenticated the current request. This
 * lets a user see and revoke their *other* logged-in devices (the mobile
 * app) from a web session; the web session itself is cookie-based, never
 * one of these Sanctum tokens, so it never appears in this list —
 * `PersonalAccessTokenResource::isCurrent` is always false here, on
 * purpose, and the page copy says so plainly.
 */
class SessionController extends Controller
{
    public function index(Request $request): Response
    {
        $tokens = $request->user()->tokens()->orderByDesc('last_used_at')->get();

        return Inertia::render('settings/sessions', [
            'sessions' => PersonalAccessTokenResource::collection($tokens)->resolve(),
        ]);
    }

    public function destroy(Request $request, string $tokenId): RedirectResponse
    {
        $token = $request->user()->tokens()->whereKey($tokenId)->first();

        if (! $token) {
            throw new ModelNotFoundException;
        }

        $token->delete();

        return back();
    }
}
