<?php

use App\Models\Unit;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the units index lists units', function () {
    Unit::query()->create(['name' => 'Piece', 'code' => 'pcs', 'factor' => 1]);

    $this->get(route('units.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('units/Index')
            ->has('units.data', 1)
            ->where('units.data.0.code', 'pcs')
        );
});

test('a unit can be created', function () {
    $this->post(route('units.store'), [
        'name' => 'Box',
        'code' => 'BOX',
        'factor' => '12',
        'is_active' => '1',
    ])->assertRedirect(route('units.index'));

    $unit = Unit::query()->where('name', 'Box')->first();

    expect($unit?->code)->toBe('box')
        ->and((float) $unit?->factor)->toBe(12.0)
        ->and($unit?->is_active)->toBeTrue();
});

test('a unit requires a name and code', function () {
    $this->post(route('units.store'), [])
        ->assertSessionHasErrors(['name', 'code']);

    expect(Unit::query()->count())->toBe(0);
});

test('a unit factor defaults to one', function () {
    $this->post(route('units.store'), [
        'name' => 'Each',
        'code' => 'ea',
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    expect((float) Unit::query()->where('code', 'ea')->first()?->factor)->toBe(1.0);
});

test('a unit code must be unique', function () {
    Unit::query()->create(['name' => 'Box', 'code' => 'box', 'factor' => 12]);

    $this->post(route('units.store'), [
        'name' => 'Another Box',
        'code' => 'BOX',
        'factor' => '6',
    ])->assertSessionHasErrors('code');
});

test('a unit can reference a base unit', function () {
    $piece = Unit::query()->create(['name' => 'Piece', 'code' => 'pcs', 'factor' => 1]);

    $this->post(route('units.store'), [
        'name' => 'Box',
        'code' => 'box',
        'base_unit_id' => $piece->id,
        'factor' => '12',
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    expect(Unit::query()->where('code', 'box')->first()?->base_unit_id)
        ->toBe($piece->id);
});

test('a unit cannot be its own base unit', function () {
    $unit = Unit::query()->create(['name' => 'Box', 'code' => 'box', 'factor' => 12]);

    $this->put(route('units.update', $unit), [
        'name' => 'Box',
        'code' => 'box',
        'base_unit_id' => $unit->id,
        'factor' => '12',
        'is_active' => '1',
    ])->assertSessionHasErrors('base_unit_id');
});

test('a unit can be updated', function () {
    $unit = Unit::query()->create(['name' => 'Piece', 'code' => 'pcs', 'factor' => 1]);

    $this->put(route('units.update', $unit), [
        'name' => 'Pieces',
        'code' => 'pcs',
        'factor' => '1',
        'is_active' => '0',
    ])->assertRedirect(route('units.index'));

    expect($unit->refresh())
        ->name->toBe('Pieces')
        ->is_active->toBeFalse();
});

test('a unit can be deleted', function () {
    $unit = Unit::query()->create(['name' => 'Piece', 'code' => 'pcs', 'factor' => 1]);

    $this->delete(route('units.destroy', $unit))
        ->assertRedirect(route('units.index'));

    expect($unit->fresh())->toBeNull();
});
