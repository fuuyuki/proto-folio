<?php

namespace App\Support;

use App\Models\Setting;

final class BusinessProfile
{
    /**
     * The business header printed on invoices.
     *
     * Falls back to the application name until the user fills in their own.
     *
     * @return array{name: string, address: string|null}
     */
    public static function toArray(): array
    {
        $settings = Setting::query()
            ->whereIn('key', ['business_name', 'business_address'])
            ->pluck('value', 'key');

        $name = $settings->get('business_name');
        $address = $settings->get('business_address');

        return [
            'name' => filled($name) ? (string) $name : (string) config('app.name'),
            'address' => filled($address) ? (string) $address : null,
        ];
    }
}
