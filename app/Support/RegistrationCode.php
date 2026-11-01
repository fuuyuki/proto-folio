<?php

namespace App\Support;

use App\Models\Setting;
use PragmaRX\Google2FA\Google2FA;

/**
 * The shared TOTP secret that gates account registration.
 */
class RegistrationCode
{
    private const SETTING_KEY = 'registration_totp_secret';

    private const SECRET_LENGTH = 32;

    /**
     * The current secret, generating one on first use.
     */
    public function secret(): string
    {
        $secret = Setting::query()->where('key', self::SETTING_KEY)->value('value');

        if (is_string($secret) && $secret !== '') {
            return $secret;
        }

        return $this->rotate();
    }

    /**
     * Generate a new secret, invalidating every configured authenticator entry.
     */
    public function rotate(): string
    {
        $secret = (new Google2FA)->generateSecretKey(self::SECRET_LENGTH);

        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => $secret, 'type' => 'string'],
        );

        return $secret;
    }

    /**
     * Determine whether the given code matches the current secret.
     *
     * A window of one period tolerates clock drift and a code typed just
     * before it rolled over.
     */
    public function verify(string $code): bool
    {
        return (new Google2FA)->verifyKey($this->secret(), $code, 1) !== false;
    }

    /**
     * The otpauth:// URI an authenticator app can import.
     */
    public function otpauthUri(string $issuer, string $account = 'staff'): string
    {
        return (new Google2FA)->getQRCodeUrl($issuer, $account, $this->secret());
    }
}
