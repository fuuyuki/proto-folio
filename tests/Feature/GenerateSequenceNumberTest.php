<?php

use App\Actions\NumberSequences\GenerateSequenceNumber;
use App\Enums\SequenceResetPeriod;
use App\Models\NumberSequence;
use Illuminate\Support\Carbon;

function makeNumberSequence(array $attributes = []): NumberSequence
{
    return NumberSequence::query()->create([
        'key' => 'product',
        'name' => 'Product SKU',
        'format' => '{PREFIX}-{SEQ:4}',
        'prefix' => 'PRD',
        'next_number' => 1,
        'padding' => 4,
        'reset_period' => SequenceResetPeriod::Never,
        ...$attributes,
    ]);
}

test('it generates padded sequential numbers', function () {
    makeNumberSequence();

    $generator = app(GenerateSequenceNumber::class);

    expect($generator->handle('product'))->toBe('PRD-0001')
        ->and($generator->handle('product'))->toBe('PRD-0002')
        ->and(NumberSequence::query()->first()?->next_number)->toBe(3);
});

test('it falls back to the sequence padding when the placeholder has none', function () {
    makeNumberSequence(['format' => '{PREFIX}-{SEQ}', 'padding' => 6]);

    expect(app(GenerateSequenceNumber::class)->handle('product'))
        ->toBe('PRD-000001');
});

test('it expands date placeholders', function () {
    $this->travelTo(Carbon::parse('2026-03-09 15:30:00'));

    makeNumberSequence([
        'format' => '{PREFIX}-{YYYY}{MM}{DD}-{SEQ:3}',
        'prefix' => 'SALE',
        'padding' => 3,
    ]);

    expect(app(GenerateSequenceNumber::class)->handle('product'))
        ->toBe('SALE-20260309-001');
});

test('it resets a daily sequence after the day rolls over', function () {
    $this->travelTo(Carbon::parse('2026-03-09 10:00:00'));

    $sequence = makeNumberSequence([
        'key' => 'sale',
        'reset_period' => SequenceResetPeriod::Daily,
        'next_number' => 42,
        'last_reset_at' => Carbon::parse('2026-03-08 18:00:00'),
    ]);

    expect(app(GenerateSequenceNumber::class)->handle('sale'))
        ->toBe('PRD-0001');

    $sequence->refresh();

    expect($sequence->next_number)->toBe(2)
        ->and($sequence->last_reset_at?->isSameDay(Carbon::parse('2026-03-09')))->toBeTrue();
});

test('it keeps counting within the same reset period', function () {
    $this->travelTo(Carbon::parse('2026-03-09 10:00:00'));

    makeNumberSequence([
        'reset_period' => SequenceResetPeriod::Daily,
        'next_number' => 7,
        'last_reset_at' => Carbon::parse('2026-03-09 08:00:00'),
    ]);

    expect(app(GenerateSequenceNumber::class)->handle('product'))
        ->toBe('PRD-0007');
});

test('it resets a monthly sequence when the month changes', function () {
    $this->travelTo(Carbon::parse('2026-04-01 09:00:00'));

    makeNumberSequence([
        'reset_period' => SequenceResetPeriod::Monthly,
        'next_number' => 12,
        'last_reset_at' => Carbon::parse('2026-03-28 09:00:00'),
    ]);

    expect(app(GenerateSequenceNumber::class)->handle('product'))
        ->toBe('PRD-0001');
});

test('it drops the leading separator when no prefix is configured', function () {
    makeNumberSequence(['prefix' => null]);

    expect(app(GenerateSequenceNumber::class)->handle('product'))
        ->toBe('0001');
});

test('it fails when the sequence is not configured', function () {
    app(GenerateSequenceNumber::class)->handle('missing');
})->throws(InvalidArgumentException::class);
