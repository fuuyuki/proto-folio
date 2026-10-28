<?php

namespace Database\Seeders;

use App\Enums\PaymentMethodType;
use App\Enums\SequenceResetPeriod;
use App\Models\Currency;
use App\Models\Location;
use App\Models\NumberSequence;
use App\Models\PaymentMethod;
use App\Models\Register;
use App\Models\Setting;
use App\Models\TaxRate;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class PosDefaultsSeeder extends Seeder
{
    /**
     * Seed the defaults a fresh install needs before ringing up a sale.
     */
    public function run(): void
    {
        $currency = Currency::query()->updateOrCreate(
            ['code' => 'IDR'],
            [
                'name' => 'Indonesian Rupiah',
                'symbol' => 'Rp',
                'decimal_places' => 0,
                'is_active' => true,
            ],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'base_currency'],
            ['value' => $currency->code, 'type' => 'string'],
        );

        Unit::query()->updateOrCreate(
            ['code' => 'pcs'],
            ['name' => 'Piece', 'base_unit_id' => null, 'factor' => 1, 'is_active' => true],
        );

        TaxRate::query()->updateOrCreate(
            ['name' => 'PPN'],
            ['rate' => 11, 'is_inclusive' => false, 'is_active' => true],
        );

        $location = Location::query()->updateOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main Store', 'is_active' => true],
        );

        Register::query()->updateOrCreate(
            ['code' => 'REG-1'],
            ['location_id' => $location->id, 'name' => 'Register 1', 'is_active' => true],
        );

        $paymentMethods = [
            ['code' => 'cash', 'name' => 'Cash', 'type' => PaymentMethodType::Cash, 'is_cash' => true, 'sort_order' => 1],
            ['code' => 'card', 'name' => 'Card', 'type' => PaymentMethodType::Card, 'is_cash' => false, 'sort_order' => 2],
            ['code' => 'transfer', 'name' => 'Bank Transfer', 'type' => PaymentMethodType::Transfer, 'is_cash' => false, 'sort_order' => 3],
            ['code' => 'qris', 'name' => 'QRIS', 'type' => PaymentMethodType::Mobile, 'is_cash' => false, 'sort_order' => 4],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $paymentMethod['code']],
                [...$paymentMethod, 'is_active' => true],
            );
        }

        $sequences = [
            [
                'key' => 'product',
                'name' => 'Product SKU',
                'format' => '{PREFIX}-{SEQ:5}',
                'prefix' => 'PRD',
                'padding' => 5,
                'reset_period' => SequenceResetPeriod::Never,
            ],
            [
                'key' => 'sale',
                'name' => 'Sale receipt',
                'format' => '{PREFIX}-{YYYYMMDD}-{SEQ:4}',
                'prefix' => 'SALE',
                'padding' => 4,
                'reset_period' => SequenceResetPeriod::Daily,
            ],
            [
                'key' => 'sale_return',
                'name' => 'Sale return',
                'format' => '{PREFIX}-{YYYYMMDD}-{SEQ:4}',
                'prefix' => 'RET',
                'padding' => 4,
                'reset_period' => SequenceResetPeriod::Daily,
            ],
        ];

        foreach ($sequences as $sequence) {
            NumberSequence::query()->updateOrCreate(
                ['key' => $sequence['key']],
                $sequence,
            );
        }
    }
}
