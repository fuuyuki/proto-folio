<?php

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PosDefaultsSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->seed(PosDefaultsSeeder::class);
});

test('the invoices index lists invoices and filters by status', function () {
    Invoice::factory()->count(2)->create(['status' => InvoiceStatus::Draft]);
    Invoice::factory()->create(['status' => InvoiceStatus::Paid]);

    $this->get(route('invoices.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('invoices/Index')
            ->has('invoices.data', 3)
        );

    $this->get(route('invoices.index', ['status' => 'paid']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.status', 'paid')
        );
});

test('invoices can be searched by number or customer name', function () {
    $customer = Customer::factory()->create(['name' => 'Globex']);
    Invoice::factory()->for($customer)->create(['number' => 'INV-2601-0099']);
    Invoice::factory()->create(['number' => 'INV-2601-0100']);

    $this->get(route('invoices.index', ['search' => 'globex']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.number', 'INV-2601-0099')
        );
});

test('the create page exposes customers and products', function () {
    Customer::factory()->create(['name' => 'Acme']);
    Product::factory()->create(['name' => 'Consulting', 'price' => 100]);

    $this->get(route('invoices.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('invoices/Create')
            ->has('customers', 1)
            ->has('products', 1)
            ->where('products.0.name', 'Consulting')
        );
});

test('an invoice can be stored with line items and calculated totals', function () {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['price' => 100]);

    $response = $this->post(route('invoices.store'), [
        'customer_id' => $customer->id,
        'status' => 'draft',
        'issue_date' => '2026-10-29',
        'due_date' => '2026-11-12',
        'notes' => 'Thanks for your business.',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100, 'tax_rate' => 11],
            ['product_id' => null, 'quantity' => 1, 'unit_price' => 50, 'tax_rate' => 0],
        ],
    ]);

    $invoice = Invoice::query()->firstOrFail();

    $response->assertRedirect(route('invoices.show', $invoice));

    expect($invoice->number)->toStartWith('INV-')
        ->and($invoice->subtotal)->toBe('250.00')
        ->and($invoice->tax_amount)->toBe('22.00')
        ->and($invoice->total_amount)->toBe('272.00')
        ->and($invoice->items()->count())->toBe(2);
});

test('an invoice must have at least one line item', function () {
    $customer = Customer::factory()->create();

    $this->post(route('invoices.store'), [
        'customer_id' => $customer->id,
        'status' => 'draft',
        'issue_date' => '2026-10-29',
        'items' => [],
    ])->assertSessionHasErrors('items');

    expect(Invoice::query()->count())->toBe(0);
});

test('updating an invoice replaces its line items and recalculates totals', function () {
    $invoice = Invoice::factory()->create();
    InvoiceItem::factory()->count(2)->for($invoice)->create();
    $customer = Customer::factory()->create();

    $this->put(route('invoices.update', $invoice), [
        'customer_id' => $customer->id,
        'status' => 'sent',
        'issue_date' => '2026-10-29',
        'due_date' => null,
        'notes' => null,
        'items' => [
            ['product_id' => null, 'quantity' => 3, 'unit_price' => 10, 'tax_rate' => 10],
        ],
    ])->assertRedirect(route('invoices.show', $invoice));

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Sent)
        ->and($invoice->subtotal)->toBe('30.00')
        ->and($invoice->tax_amount)->toBe('3.00')
        ->and($invoice->total_amount)->toBe('33.00')
        ->and($invoice->items()->count())->toBe(1);
});

test('deleting an invoice removes its line items', function () {
    $invoice = Invoice::factory()->create();
    InvoiceItem::factory()->count(2)->for($invoice)->create();

    $this->delete(route('invoices.destroy', $invoice))
        ->assertRedirect(route('invoices.index'));

    expect(Invoice::query()->count())->toBe(0)
        ->and(InvoiceItem::query()->count())->toBe(0);
});

test('the receipt page exposes the invoice for 58mm printing', function () {
    $invoice = Invoice::factory()->create();
    InvoiceItem::factory()->count(2)->for($invoice)->create();

    $this->get(route('invoices.print', $invoice))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('invoices/Print')
            ->where('business.name', config('app.name'))
            ->where('business.address', null)
            ->where('invoice.number', $invoice->number)
            ->has('items', 2)
            ->has('items.0.description')
            ->has('items.0.line_total')
            ->where('labels.discount', 'Discount')
        );
});

test('a percentage discount is applied to the subtotal before tax', function () {
    $customer = Customer::factory()->create();

    $this->post(route('invoices.store'), [
        'customer_id' => $customer->id,
        'status' => 'draft',
        'issue_date' => '2026-10-29',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'items' => [
            ['product_id' => null, 'quantity' => 2, 'unit_price' => 500, 'tax_rate' => 11],
        ],
    ])->assertRedirect();

    $invoice = Invoice::query()->firstOrFail();

    expect($invoice->subtotal)->toBe('1000.00')
        ->and($invoice->discount_type)->toBe(DiscountType::Percent)
        ->and($invoice->discount_value)->toBe('10.00')
        ->and($invoice->discount_amount)->toBe('100.00')
        ->and($invoice->tax_amount)->toBe('99.00')
        ->and($invoice->total_amount)->toBe('999.00');
});

test('a fixed discount never exceeds the subtotal', function () {
    $customer = Customer::factory()->create();

    $this->post(route('invoices.store'), [
        'customer_id' => $customer->id,
        'status' => 'draft',
        'issue_date' => '2026-10-29',
        'discount_type' => 'amount',
        'discount_value' => 500,
        'items' => [
            ['product_id' => null, 'quantity' => 1, 'unit_price' => 250, 'tax_rate' => 11],
        ],
    ])->assertRedirect();

    $invoice = Invoice::query()->firstOrFail();

    expect($invoice->discount_amount)->toBe('250.00')
        ->and($invoice->tax_amount)->toBe('0.00')
        ->and($invoice->total_amount)->toBe('0.00');
});

test('a percentage discount cannot exceed one hundred percent', function () {
    $customer = Customer::factory()->create();

    $this->post(route('invoices.store'), [
        'customer_id' => $customer->id,
        'status' => 'draft',
        'issue_date' => '2026-10-29',
        'discount_type' => 'percent',
        'discount_value' => 120,
        'items' => [
            ['product_id' => null, 'quantity' => 1, 'unit_price' => 100, 'tax_rate' => 0],
        ],
    ])->assertSessionHasErrors('discount_value');

    expect(Invoice::query()->count())->toBe(0);
});

test('updating an invoice recalculates and can clear the discount', function () {
    $invoice = Invoice::factory()->create([
        'discount_type' => 'percent',
        'discount_value' => 10,
        'discount_amount' => 100,
    ]);
    $customer = Customer::factory()->create();

    $payload = [
        'customer_id' => $customer->id,
        'status' => 'draft',
        'issue_date' => '2026-10-29',
        'discount_type' => 'amount',
        'discount_value' => 50,
        'items' => [
            ['product_id' => null, 'quantity' => 1, 'unit_price' => 200, 'tax_rate' => 0],
        ],
    ];

    $this->put(route('invoices.update', $invoice), $payload)->assertRedirect();

    $invoice->refresh();

    expect($invoice->discount_amount)->toBe('50.00')
        ->and($invoice->total_amount)->toBe('150.00');

    $this->put(route('invoices.update', $invoice), [
        ...$payload,
        'discount_type' => 'none',
    ])->assertRedirect();

    $invoice->refresh();

    expect($invoice->discount_type)->toBe(DiscountType::None)
        ->and($invoice->discount_value)->toBe('0.00')
        ->and($invoice->discount_amount)->toBe('0.00')
        ->and($invoice->total_amount)->toBe('200.00');
});
