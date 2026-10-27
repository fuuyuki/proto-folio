<?php

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\QueryException;

it('links an invoice to its customer and line items', function () {
    $invoice = Invoice::factory()->create();
    InvoiceItem::factory()->count(2)->for($invoice)->create();

    expect($invoice->customer)->toBeInstanceOf(Customer::class)
        ->and($invoice->items)->toHaveCount(2)
        ->and($invoice->items->first()->invoice->is($invoice))->toBeTrue();
});

it('casts the status column to the invoice status enum', function () {
    $invoice = Invoice::factory()->paid()->create();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

it('keeps invoice items when their product is deleted', function () {
    $item = InvoiceItem::factory()->create();

    $item->product->delete();

    expect($item->fresh()->product_id)->toBeNull();
});

it('prevents deleting a customer that has invoices', function () {
    $invoice = Invoice::factory()->create();

    expect(fn () => $invoice->customer->delete())
        ->toThrow(QueryException::class);

    expect(Customer::query()->whereKey($invoice->customer_id)->exists())->toBeTrue();
});
