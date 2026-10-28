<?php

use App\Models\TaxRate;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the tax rates index lists tax rates', function () {
    TaxRate::query()->create(['name' => 'PPN', 'rate' => 11]);

    $this->get(route('tax-rates.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('tax-rates/Index')
            ->has('taxRates.data', 1)
            ->where('taxRates.data.0.name', 'PPN')
        );
});

test('a tax rate can be created', function () {
    $this->post(route('tax-rates.store'), [
        'name' => 'PPN',
        'rate' => '11',
        'is_inclusive' => '0',
        'is_active' => '1',
    ])->assertRedirect(route('tax-rates.index'));

    $taxRate = TaxRate::query()->where('name', 'PPN')->first();

    expect((float) $taxRate?->rate)->toBe(11.0)
        ->and($taxRate?->is_inclusive)->toBeFalse()
        ->and($taxRate?->is_active)->toBeTrue();
});

test('a tax rate must be between zero and one hundred', function () {
    $this->post(route('tax-rates.store'), [
        'name' => 'Too high',
        'rate' => '101',
    ])->assertSessionHasErrors('rate');
});

test('a tax rate can be marked as inclusive', function () {
    $this->post(route('tax-rates.store'), [
        'name' => 'VAT',
        'rate' => '20',
        'is_inclusive' => '1',
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    expect(TaxRate::query()->where('name', 'VAT')->first()?->is_inclusive)->toBeTrue();
});

test('a tax rate can be updated', function () {
    $taxRate = TaxRate::query()->create(['name' => 'PPN', 'rate' => 11]);

    $this->put(route('tax-rates.update', $taxRate), [
        'name' => 'PPN 12',
        'rate' => '12',
        'is_inclusive' => '0',
        'is_active' => '1',
    ])->assertRedirect(route('tax-rates.index'));

    expect($taxRate->refresh())
        ->name->toBe('PPN 12');
});

test('a tax rate can be deleted', function () {
    $taxRate = TaxRate::query()->create(['name' => 'PPN', 'rate' => 11]);

    $this->delete(route('tax-rates.destroy', $taxRate))
        ->assertRedirect(route('tax-rates.index'));

    expect($taxRate->fresh())->toBeNull();
});
