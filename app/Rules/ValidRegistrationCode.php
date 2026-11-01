<?php

namespace App\Rules;

use App\Support\RegistrationCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\RateLimiter;

class ValidRegistrationCode implements ValidationRule
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $key = 'registration-code:'.(string) request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $fail((string) __('Too many registration attempts. Try again in :seconds seconds.', [
                'seconds' => RateLimiter::availableIn($key),
            ]));

            return;
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        if (! is_string($value) || ! app(RegistrationCode::class)->verify($value)) {
            $fail((string) __('The registration code is invalid or has expired.'));
        }
    }
}
