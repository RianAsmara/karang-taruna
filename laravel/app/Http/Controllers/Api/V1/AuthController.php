<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\PersonalAccessTokenResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Mirrors Auth\RegisteredUserController's web flow (same account
     * creation, same Registered event) but issues a Sanctum token
     * instead of starting a session — the mobile client has no signup
     * screen otherwise, unlike web's Laravel-starter-kit registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $request->ensureIsNotRateLimited();

        $user = User::create([
            'name' => $request->string('name')->value(),
            'email' => $request->string('email')->value(),
            'password' => Hash::make($request->string('password')->value()),
        ]);

        event(new Registered($user));

        $token = $user->createToken($request->string('device_name')->value());

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                // Every other endpoint is gated by 'verified'. Returning
                // this lets the client show the "check your email" screen
                // straight away rather than after a 403 it can't explain.
                'emailVerified' => $user->hasVerifiedEmail(),
            ],
        ], 201);
    }

    /**
     * Issue a personal access token for a mobile/API client. Mirrors the
     * official Sanctum "mobile API tokens" pattern — this is a separate,
     * stateless flow from the web session login in
     * Auth/AuthenticatedSessionController, not a duplicate of it.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->ensureIsNotRateLimited();

        $user = User::where('email', $request->string('email')->value())->first();

        if (! $user || ! Hash::check($request->string('password')->value(), $user->password)) {
            RateLimiter::hit($request->throttleKey());

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        RateLimiter::clear($request->throttleKey());

        $token = $user->createToken($request->string('device_name')->value());

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                // Every other endpoint is gated by 'verified'. Returning
                // this lets the client show the "check your email" screen
                // straight away rather than after a 403 it can't explain.
                'emailVerified' => $user->hasVerifiedEmail(),
            ],
        ], 201);
    }

    /**
     * Re-send the verification link. Outside the 'verified' middleware by
     * necessity — the user asking for it is precisely the one who cannot
     * pass it.
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email Anda sudah terverifikasi.'], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Tautan verifikasi baru sudah dikirim ke '.$user->email.'.',
        ], 202);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    /**
     * List the authenticated user's active devices/sessions (Sanctum
     * tokens) — so a user can see, and revoke, a device other than the
     * one they're currently using.
     */
    public function sessions(Request $request): AnonymousResourceCollection
    {
        $tokens = $request->user()->tokens()->orderByDesc('last_used_at')->get();

        return PersonalAccessTokenResource::collection($tokens);
    }

    /**
     * Revoke one of the user's own tokens by id — including, harmlessly,
     * the current one (equivalent to calling logout()).
     */
    public function destroySession(Request $request, string $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->whereKey($tokenId)->first();

        if (! $token) {
            throw new ModelNotFoundException;
        }

        $token->delete();

        return response()->json(null, 204);
    }
}
