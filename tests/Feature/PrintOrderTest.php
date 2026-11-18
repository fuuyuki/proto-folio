<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Product;
use Database\Seeders\PosDefaultsSeeder;
use Database\Seeders\PublicCatalogSeeder;

beforeEach(function () {
    $this->seed(PosDefaultsSeeder::class);
    $this->seed(PublicCatalogSeeder::class);
});

/**
 * A valid public order payload.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function print_order_payload(array $overrides = []): array
{
    return array_replace_recursive([
        'fulfillment' => 'pickup',
        'customer' => [
            'name' => 'Nadia Putri',
            'email' => 'nadia@example.com',
            'whatsapp' => '+62 812 3456 7890',
            'note' => 'Please use the black spool.',
        ],
        'shipping' => null,
        'model' => [
            'name' => 'bracket.stl',
            'size' => '120 × 80 × 40 mm',
        ],
        'print' => [
            'filament_name' => 'PLA',
            'filament_color' => 'Black',
            'weight_g' => 123.4,
            'print_hours' => 5.5,
            'price' => 125000,
        ],
        'idempotency_key' => 'order-'.uniqid(),
        'website' => '',
    ], $overrides);
}

test('a public order creates a customer, invoice, and item', function () {
    $payload = print_order_payload();

    $response = $this->withHeaders([
        'Origin' => 'https://protofylabs.web.id',
    ])->postJson(route('api.v1.orders.print'), $payload)
        ->assertCreated()
        ->assertHeader('Access-Control-Allow-Origin', 'https://protofylabs.web.id')
        ->assertJsonStructure([
            'invoice_number',
            'payment_url',
            'total',
            'currency',
        ]);

    $invoice = Invoice::query()->with('customer', 'items')->firstOrFail();
    $product = Product::query()->where('slug', 'custom-3d-printing')->firstOrFail();

    expect($invoice->status)->toBe(InvoiceStatus::Sent)
        ->and($invoice->customer->name)->toBe('Nadia Putri')
        ->and($invoice->customer->email)->toBe('nadia@example.com')
        ->and($invoice->customer->phone)->toBe('+62 812 3456 7890')
        ->and($invoice->subtotal)->toBe('125000.00')
        ->and($invoice->tax_amount)->toBe('0.00')
        ->and($invoice->total_amount)->toBe('125000.00')
        ->and($invoice->items)->toHaveCount(1)
        ->and($invoice->items->first()->product_id)->toBe($product->id)
        ->and($invoice->items->first()->unit_price)->toBe('125000.00')
        ->and($invoice->items->first()->tax_rate)->toBe('0.00')
        ->and($invoice->notes)->toContain('Fulfillment: Pickup')
        ->and($invoice->notes)->toContain('70% infill')
        ->and($invoice->notes)->toContain('Customer note:')
        ->and($response->json('payment_url'))->toContain('/pay/')
        ->and($response->json('invoice_number'))->toBe($invoice->number);

    $this->get($response->json('payment_url'))->assertOk();
});

test('a shipment order stores the address on the customer and the notes', function () {
    $payload = print_order_payload([
        'fulfillment' => 'shipment',
        'shipping' => [
            'label' => 'Office',
            'address' => 'Jl. Merdeka 10',
            'detail' => 'Floor 3, reception',
            'city' => 'Bandung',
            'province' => 'West Java',
            'postal_code' => '40111',
        ],
    ]);

    $this->postJson(route('api.v1.orders.print'), $payload)->assertCreated();

    $invoice = Invoice::query()->with('customer')->firstOrFail();
    $address = $invoice->customer->address;

    expect($address)->toContain('Jl. Merdeka 10')
        ->and($address)->toContain('Floor 3, reception')
        ->and($address)->toContain('Bandung')
        ->and($address)->toContain('40111')
        ->and($invoice->notes)->toContain('Fulfillment: Shipment')
        ->and($invoice->notes)->toContain('Address label: Office')
        ->and($invoice->notes)->toContain('Postal code: 40111');
});

test('a repeated idempotency key returns the same invoice', function () {
    $payload = print_order_payload();

    $first = $this->postJson(route('api.v1.orders.print'), $payload)
        ->assertCreated();

    $second = $this->postJson(route('api.v1.orders.print'), $payload)
        ->assertCreated();

    expect(Invoice::query()->count())->toBe(1)
        ->and($second->json('invoice_number'))->toBe($first->json('invoice_number'))
        ->and($second->json('payment_url'))->toBe($first->json('payment_url'));
});

test('a shipment order requires the address', function () {
    $payload = print_order_payload([
        'fulfillment' => 'shipment',
        'shipping' => null,
    ]);

    $this->postJson(route('api.v1.orders.print'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'shipping.label',
            'shipping.address',
            'shipping.city',
            'shipping.province',
            'shipping.postal_code',
        ]);

    expect(Invoice::query()->count())->toBe(0);
});

test('the postal code must be five digits', function () {
    $payload = print_order_payload([
        'fulfillment' => 'shipment',
        'shipping' => [
            'label' => 'Home',
            'address' => 'Jl. Merdeka 10',
            'city' => 'Bandung',
            'province' => 'West Java',
            'postal_code' => '401',
        ],
    ]);

    $this->postJson(route('api.v1.orders.print'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('shipping.postal_code');
});

test('the customer contact details are required', function () {
    $payload = print_order_payload([
        'customer' => [
            'name' => '',
            'email' => 'not-an-email',
            'whatsapp' => 'abc',
        ],
    ]);

    $this->postJson(route('api.v1.orders.print'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'customer.name',
            'customer.email',
            'customer.whatsapp',
        ]);
});

test('the anti-spam honeypot rejects a filled value', function () {
    $payload = print_order_payload(['website' => 'https://spam.example']);

    $this->postJson(route('api.v1.orders.print'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('website');
});

test('a price below the minimum is rejected', function () {
    $payload = print_order_payload([
        'print' => ['price' => 500],
    ]);

    $this->postJson(route('api.v1.orders.print'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('print.price');
});
