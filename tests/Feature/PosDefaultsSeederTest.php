<?php

use App\Enums\SequenceResetPeriod;
use App\Models\Currency;
use App\Models\Location;
use App\Models\NumberSequence;
use App\Models\PaymentMethod;
use App\Models\TaxRate;
use App\Models\Unit;
use Database\Seeders\PosDefaultsSeeder;

test('it seeds the defaults needed to ring up a sale', function () {
    $this->seed(PosDefaultsSeeder::class);

    expect(Currency::query()->where('code', 'IDR')->exists())->toBeTrue()
        ->and(Unit::query()->where('code', 'pcs')->exists())->toBeTrue()
        ->and(TaxRate::query()->where('name', 'PPN')->exists())->toBeTrue()
        ->and(Location::query()->where('code', 'MAIN')->exists())->toBeTrue()
        ->and(PaymentMethod::query()->count())->toBe(4)
        ->and(NumberSequence::query()->count())->toBe(4)
        ->and(NumberSequence::query()->where('key', 'sale')->first()?->reset_period)
        ->toBe(SequenceResetPeriod::Daily)
        ->and(NumberSequence::query()->where('key', 'invoice')->first()?->reset_period)
        ->toBe(SequenceResetPeriod::Monthly);
});

test('it can be run more than once without duplicating rows', function () {
    $this->seed(PosDefaultsSeeder::class);
    $this->seed(PosDefaultsSeeder::class);

    expect(Currency::query()->count())->toBe(1)
        ->and(Location::query()->count())->toBe(1)
        ->and(PaymentMethod::query()->count())->toBe(4)
        ->and(NumberSequence::query()->count())->toBe(4);
});
