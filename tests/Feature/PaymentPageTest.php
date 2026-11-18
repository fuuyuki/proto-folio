<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;

test('a signed invoice link shows a qris code for the invoice amount', function () {
    Setting::query()->create([
        'key' => 'qris_payload',
        'value' => make_qris_payload(),
        'type' => 'text',
    ]);

    $invoice = Invoice::factory()->sent()->create([
        'subtotal' => 125000,
        'tax_amount' => 0,
        'total_amount' => 125000,
    ]);

    $url = URL::signedRoute('payments.show', ['invoice' => $invoice->number]);

    $this->get($url)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('payments/Show')
            ->where('invoice.number', $invoice->number)
            ->where('paid', false)
            ->where('qris_error', null)
            ->where('qris_data_uri', fn (mixed $value): bool => is_string($value)
                && str_starts_with($value, 'data:image/svg+xml;base64,'))
        );
});

test('an unsigned invoice link is rejected', function () {
    $invoice = Invoice::factory()->sent()->create();

    $this->get(route('payments.show', $invoice->number))->assertForbidden();
});

test('a link signed for another invoice is rejected', function () {
    $first = Invoice::factory()->sent()->create();
    $second = Invoice::factory()->sent()->create();

    $url = URL::signedRoute('payments.show', ['invoice' => $first->number]);
    $tampered = str_replace($first->number, $second->number, $url);

    $this->get($tampered)->assertForbidden();
});

test('a missing qris setting shows a contact message instead of a code', function () {
    $invoice = Invoice::factory()->sent()->create();

    $url = URL::signedRoute('payments.show', ['invoice' => $invoice->number]);

    $this->get($url)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('qris_data_uri', null)
            ->where('qris_error', fn (mixed $value): bool => is_string($value)
                && str_contains($value, 'not configured'))
        );
});

test('a paid invoice shows the paid state and no qris code', function () {
    Setting::query()->create([
        'key' => 'qris_payload',
        'value' => make_qris_payload(),
        'type' => 'text',
    ]);

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid]);

    $url = URL::signedRoute('payments.show', ['invoice' => $invoice->number]);

    $this->get($url)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('paid', true)
            ->where('qris_data_uri', null)
            ->where('qris_error', null)
        );
});

test('a draft invoice cannot be paid yet', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    $url = URL::signedRoute('payments.show', ['invoice' => $invoice->number]);

    $this->get($url)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('paid', false)
            ->where('qris_data_uri', null)
            ->where('qris_error', fn (mixed $value): bool => is_string($value)
                && str_contains($value, 'not ready'))
        );
});
