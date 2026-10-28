<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PosDefaultsSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->seed(PosDefaultsSeeder::class);
});

test('the products index lists products', function () {
    $category = ProductCategory::query()->create([
        'name' => 'Consulting',
        'slug' => 'consulting',
    ]);

    Product::factory()->create([
        'name' => 'Consulting Hour',
        'sku' => 'CONSULT-1',
        'category_id' => $category->id,
    ]);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('products/Index')
            ->has('products.data', 1)
            ->where('products.data.0.sku', 'CONSULT-1')
            ->where('products.data.0.type', 'product')
            ->where('products.data.0.category.name', 'Consulting')
            ->where('products.data.0.invoice_items_count', 0)
        );
});

test('products can be searched by name or sku', function () {
    Product::factory()->create(['name' => 'Design Sprint', 'sku' => 'DESIGN-1']);
    Product::factory()->create(['name' => 'Hosting', 'sku' => 'HOST-1']);

    $this->get(route('products.index', ['search' => 'design']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Design Sprint')
        );

    $this->get(route('products.index', ['search' => 'HOST-1']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Hosting')
        );
});

test('the create page exposes the form options', function () {
    $this->get(route('products.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('products/Create')
            ->has('categories', 0)
            ->has('units', 1)
            ->has('taxRates', 1)
        );
});

test('a product can be created with its catalog details', function () {
    $category = ProductCategory::query()->create([
        'name' => 'Drinks',
        'slug' => 'drinks',
    ]);
    $unit = Unit::query()->where('code', 'pcs')->firstOrFail();
    $taxRate = TaxRate::query()->where('name', 'PPN')->firstOrFail();

    $this->post(route('products.store'), [
        'name' => 'Consulting Hour',
        'sku' => 'CONSULT-1',
        'barcode' => '8991234567890',
        'type' => 'product',
        'price' => '125.50',
        'cost_price' => '60.00',
        'description' => 'One hour of consulting.',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'tax_rate_id' => $taxRate->id,
        'track_stock' => '1',
        'is_active' => '1',
    ])->assertRedirect(route('products.index'));

    $product = Product::query()->where('sku', 'CONSULT-1')->first();

    expect($product)->not->toBeNull()
        ->and($product->price)->toBe('125.50')
        ->and($product->cost_price)->toBe('60.00')
        ->and($product->barcode)->toBe('8991234567890')
        ->and($product->category_id)->toBe($category->id)
        ->and($product->unit_id)->toBe($unit->id)
        ->and($product->tax_rate_id)->toBe($taxRate->id)
        ->and($product->track_stock)->toBeTrue()
        ->and($product->is_active)->toBeTrue();
});

test('a product sku is generated when omitted', function () {
    $this->post(route('products.store'), [
        'name' => 'Generated Item',
        'type' => 'product',
        'price' => '10.00',
        'is_active' => '1',
    ])->assertRedirect(route('products.index'));

    $product = Product::query()->where('name', 'Generated Item')->first();

    expect($product?->sku)->toBe('PRD-00001')
        ->and($product?->cost_price)->toBe('0.00');
});

test('a product requires a name, type, and price', function () {
    $this->post(route('products.store'), [])
        ->assertSessionHasErrors(['name', 'type', 'price']);

    expect(Product::query()->count())->toBe(0);
});

test('a service never tracks stock', function () {
    $this->post(route('products.store'), [
        'name' => 'Installation',
        'type' => 'service',
        'price' => '500000',
        'track_stock' => '1',
        'is_active' => '1',
    ])->assertRedirect(route('products.index'));

    expect(Product::query()->where('name', 'Installation')->first()?->track_stock)
        ->toBeFalse();
});

test('a product sku must be unique', function () {
    Product::factory()->create(['sku' => 'SKU-1']);

    $this->post(route('products.store'), [
        'name' => 'Another Product',
        'sku' => 'SKU-1',
        'type' => 'product',
        'price' => '10.00',
    ])->assertSessionHasErrors('sku');
});

test('the edit page exposes the product and form options', function () {
    $product = Product::factory()->create();

    $this->get(route('products.edit', $product))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('products/Edit')
            ->where('product.id', $product->id)
            ->has('categories', 0)
            ->has('units', 1)
            ->has('taxRates', 1)
        );
});

test('a product can be updated while keeping its sku', function () {
    $product = Product::factory()->create(['sku' => 'SKU-1', 'name' => 'Old Name']);

    $this->put(route('products.update', $product), [
        'name' => 'New Name',
        'sku' => 'SKU-1',
        'type' => 'product',
        'price' => '42.00',
        'is_active' => '1',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('products.index'));

    expect($product->refresh())
        ->name->toBe('New Name')
        ->price->toBe('42.00');
});

test('a blank sku keeps the existing one on update', function () {
    $product = Product::factory()->create(['sku' => 'KEEP-1']);

    $this->put(route('products.update', $product), [
        'name' => 'Renamed',
        'sku' => '',
        'type' => 'product',
        'price' => '5.00',
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    expect($product->refresh()->sku)->toBe('KEEP-1');
});

test('a product can be deleted', function () {
    $product = Product::factory()->create();

    $this->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    expect($product->fresh())->toBeNull();
});
