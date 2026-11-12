<?php

namespace App\Actions\Currencies;

use App\Models\Currency;
use App\Models\Setting;

class ResolveBaseCurrency
{
    /**
     * The base currency used to display money throughout the application.
     *
     * @return array{code: string, symbol: string, decimal_places: int}
     */
    public function handle(): array
    {
        $code = Setting::query()->where('key', 'base_currency')->value('value') ?? 'IDR';

        $currency = Currency::query()->where('code', $code)->first();

        return [
            'code' => $currency->code ?? $code,
            'symbol' => $currency->symbol ?? $code,
            'decimal_places' => $currency->decimal_places ?? 2,
        ];
    }
}
