<?php

use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the products index lists products', function () {
    Product::factory()->create(['name' => 'Consulting Hour', 'sku' => 'CONSULT-1']);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('products/Index')
            ->has('products.data', 1)
            ->where('products.data.0.sku', 'CONSULT-1')
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

test('a product can be created', function () {
    $this->post(route('products.store'), [
        'name' => 'Consulting Hour',
        'sku' => 'CONSULT-1',
        'price' => '125.50',
        'description' => 'One hour of consulting.',
    ])->assertRedirect(route('products.index'));

    $product = Product::query()->where('sku', 'CONSULT-1')->first();

    expect($product)->not->toBeNull()
        ->and($product->price)->toBe('125.50');
});

test('a product requires a name, sku, and price', function () {
    $this->post(route('products.store'), [])
        ->assertSessionHasErrors(['name', 'sku', 'price']);

    expect(Product::query()->count())->toBe(0);
});

test('a product sku must be unique', function () {
    Product::factory()->create(['sku' => 'SKU-1']);

    $this->post(route('products.store'), [
        'name' => 'Another Product',
        'sku' => 'SKU-1',
        'price' => '10.00',
    ])->assertSessionHasErrors('sku');
});

test('the edit page exposes the product', function () {
    $product = Product::factory()->create();

    $this->get(route('products.edit', $product))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('products/Edit')
            ->where('product.id', $product->id)
        );
});

test('a product can be updated while keeping its sku', function () {
    $product = Product::factory()->create(['sku' => 'SKU-1', 'name' => 'Old Name']);

    $this->put(route('products.update', $product), [
        'name' => 'New Name',
        'sku' => 'SKU-1',
        'price' => '42.00',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('products.index'));

    expect($product->refresh())
        ->name->toBe('New Name')
        ->price->toBe('42.00');
});

test('a product can be deleted', function () {
    $product = Product::factory()->create();

    $this->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    expect($product->fresh())->toBeNull();
});
