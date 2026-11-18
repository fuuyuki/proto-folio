<?php

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the business settings page shows the stored details', function () {
    Setting::query()->create(['key' => 'business_name', 'value' => 'Kopi Senja', 'type' => 'string']);
    Setting::query()->create(['key' => 'business_address', 'value' => "Jl. Merdeka 10\nBandung", 'type' => 'text']);
    Setting::query()->create(['key' => 'qris_payload', 'value' => make_qris_payload(), 'type' => 'text']);

    $this->get(route('business.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/Business')
            ->where('business.name', 'Kopi Senja')
            ->where('business.address', "Jl. Merdeka 10\nBandung")
            ->where('qris_payload', make_qris_payload())
        );
});

test('the business name falls back to the application name', function () {
    $this->get(route('business.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('business.name', config('app.name'))
            ->where('business.address', null)
        );
});

test('the business details can be updated', function () {
    $this->put(route('business.update'), [
        'name' => 'Kopi Senja',
        'address' => "Jl. Merdeka 10\nBandung",
    ])->assertRedirect(route('business.edit'));

    expect(Setting::query()->where('key', 'business_name')->value('value'))->toBe('Kopi Senja')
        ->and(Setting::query()->where('key', 'business_address')->value('value'))->toBe("Jl. Merdeka 10\nBandung");
});

test('the business name is required', function () {
    $this->put(route('business.update'), ['name' => ''])
        ->assertSessionHasErrors('name');

    expect(Setting::query()->where('key', 'business_name')->exists())->toBeFalse();
});

test('a qris payload can be saved', function () {
    $payload = make_qris_payload();

    $this->put(route('business.update'), [
        'name' => 'Kopi Senja',
        'qris_payload' => $payload,
    ])->assertRedirect(route('business.edit'));

    expect(Setting::query()->where('key', 'qris_payload')->value('value'))->toBe($payload);
});

test('an invalid qris payload is rejected', function () {
    $this->put(route('business.update'), [
        'name' => 'Kopi Senja',
        'qris_payload' => 'not-a-qris-payload',
    ])->assertSessionHasErrors('qris_payload');

    expect(Setting::query()->where('key', 'qris_payload')->exists())->toBeFalse();
});

test('the printed receipt uses the business details', function () {
    Setting::query()->create(['key' => 'business_name', 'value' => 'Kopi Senja', 'type' => 'string']);
    Setting::query()->create(['key' => 'business_address', 'value' => 'Jl. Merdeka 10, Bandung', 'type' => 'text']);

    $invoice = Invoice::factory()->create();

    $this->get(route('invoices.print', $invoice))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('invoices/Print')
            ->where('business.name', 'Kopi Senja')
            ->where('business.address', 'Jl. Merdeka 10, Bandung')
        );
});
