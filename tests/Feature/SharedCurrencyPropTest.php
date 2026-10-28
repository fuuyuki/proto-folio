<?php

use App\Models\Currency;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PosDefaultsSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the shared currency comes from the base currency setting', function () {
    $this->seed(PosDefaultsSeeder::class);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('currency.code', 'IDR')
            ->where('currency.symbol', 'Rp')
            ->where('currency.decimal_places', 0)
        );
});

test('the shared currency falls back when no base currency is configured', function () {
    Setting::query()->where('key', 'base_currency')->delete();
    Currency::query()->delete();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('currency.code', 'IDR')
            ->where('currency.symbol', 'IDR')
            ->where('currency.decimal_places', 2)
        );
});
