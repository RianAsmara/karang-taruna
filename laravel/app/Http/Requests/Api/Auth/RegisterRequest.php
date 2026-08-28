<?php

namespace App\Http\Requests\Api\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mirrors Auth\RegisteredUserController's web rules exactly — same
     * account, two client entry points.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Unlike login, there's no existing account to key attempts against
     * before validation — throttled by IP alone. Mirrors LoginRequest's
     * 5-attempts-per-minute limit; the web equivalent gets the same via
     * route `throttle:5,1` middleware instead (no FormRequest there).
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $key = 'register|'.$this->ip();

        if (! RateLimiter::tooManyAttempts($key, 5)) {
            RateLimiter::hit($key, 60);

            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }
}
