<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Setting;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the localization settings page shows the supported languages', function () {
    $this->get(route('localization.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/Localization')
            ->where('locale', 'en')
            ->where('locales.en', 'English')
            ->where('locales.id', 'Bahasa Indonesia')
        );
});

test('the language can be changed', function () {
    $this->put(route('localization.update'), ['locale' => 'id'])
        ->assertRedirect(route('localization.edit'));

    expect(Setting::query()->where('key', 'locale')->value('value'))->toBe('id');
});

test('unsupported languages are rejected', function () {
    $this->put(route('localization.update'), ['locale' => 'fr'])
        ->assertSessionHasErrors('locale');

    expect(Setting::query()->where('key', 'locale')->exists())->toBeFalse();
});

test('the receipt and invoice pages use the configured language', function () {
    Setting::query()->create(['key' => 'locale', 'value' => 'id', 'type' => 'string']);

    $invoice = Invoice::factory()->create();
    InvoiceItem::factory()->for($invoice)->create();

    $this->get(route('invoices.print', $invoice))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('invoices/Print')
            ->where('labels.receipt_title', 'FAKTUR')
            ->where('labels.billed_to', 'Ditagihkan kepada')
            ->where('labels.total', 'TOTAL')
            ->where('labels.tax', 'Pajak')
            ->where('labels.thank_you', 'Terima kasih')
        );

    $this->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('labels.print_receipt', 'Cetak struk')
            ->where('statuses.0.label', 'Draf')
            ->where('statuses.2.label', 'Lunas')
        );
});
