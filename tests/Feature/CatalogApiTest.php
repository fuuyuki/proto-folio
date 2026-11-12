<?php

use App\Models\Product;
use App\Models\ProductCategory;
use Database\Seeders\PosDefaultsSeeder;

beforeEach(function () {
    $this->seed(PosDefaultsSeeder::class);
});

test('the catalog exposes only published and active products', function () {
    $visible = Product::factory()->create([
        'name' => 'Visible Product',
        'is_published' => true,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'Draft Product',
        'is_published' => false,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'Inactive Product',
        'is_published' => true,
        'is_active' => false,
    ]);

    $this->getJson(route('api.v1.catalog'))
        ->assertOk()
        ->assertJsonCount(1, 'products')
        ->assertJsonPath('products.0.slug', $visible->slug);
});

test('the catalog skips products in inactive categories but keeps uncategorised ones', function () {
    $inactiveCategory = ProductCategory::query()->create([
        'name' => 'Archived',
        'slug' => 'archived',
        'is_active' => false,
    ]);

    $visibleCategory = ProductCategory::query()->create([
        'name' => 'Active',
        'slug' => 'active',
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'Archived Item',
        'category_id' => $inactiveCategory->id,
        'is_published' => true,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'Categorised Item',
        'category_id' => $visibleCategory->id,
        'is_published' => true,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'Uncategorised Item',
        'category_id' => null,
        'is_published' => true,
        'is_active' => true,
    ]);

    $this->getJson(route('api.v1.catalog'))
        ->assertOk()
        ->assertJsonCount(2, 'products')
        ->assertJsonPath('products.0.name', 'Categorised Item')
        ->assertJsonPath('products.0.category', 'active')
        ->assertJsonPath('products.1.name', 'Uncategorised Item')
        ->assertJsonPath('products.1.category', null);
});

test('the catalog is ordered by sort order and then name', function () {
    Product::factory()->create([
        'name' => 'Second',
        'sort_order' => 2,
        'is_published' => true,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'First B',
        'sort_order' => 1,
        'is_published' => true,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'First A',
        'sort_order' => 1,
        'is_published' => true,
        'is_active' => true,
    ]);

    $this->getJson(route('api.v1.catalog'))
        ->assertOk()
        ->assertJsonPath('products.0.name', 'First A')
        ->assertJsonPath('products.1.name', 'First B')
        ->assertJsonPath('products.2.name', 'Second');
});

test('the catalog never exposes internal product fields', function () {
    Product::factory()->create([
        'name' => 'Private Columns',
        'sku' => 'SECRET-SKU',
        'barcode' => '8991234567890',
        'cost_price' => '10.00',
        'price' => '25.00',
        'is_published' => true,
        'is_active' => true,
    ]);

    $product = $this->getJson(route('api.v1.catalog'))
        ->assertOk()
        ->json('products.0');

    expect($product)
        ->not->toHaveKeys([
            'id',
            'sku',
            'barcode',
            'cost_price',
            'track_stock',
            'stock_levels',
            'is_active',
            'is_published',
        ])
        ->and($product['name'])->toBe('Private Columns')
        ->and($product['price']['amount'])->toEqual(25);
});

test('a product can hide its price', function () {
    Product::factory()->create([
        'name' => 'Contact Only',
        'price' => '1250000',
        'show_price' => false,
        'is_published' => true,
        'is_active' => true,
    ]);

    $this->getJson(route('api.v1.catalog'))
        ->assertOk()
        ->assertJsonPath('products.0.price', null)
        ->assertJsonPath('products.0.price_display', 'contact');
});

test('the catalog formats the price in the base currency', function () {
    Product::factory()->create([
        'name' => 'Priced Item',
        'price' => '1250000',
        'show_price' => true,
        'is_published' => true,
        'is_active' => true,
    ]);

    $response = $this->getJson(route('api.v1.catalog'))
        ->assertOk()
        ->assertJsonPath('currency.code', 'IDR')
        ->assertJsonPath('currency.symbol', 'Rp')
        ->assertJsonPath('products.0.price.amount', 1250000);

    expect($response->json('products.0.price.formatted'))
        ->toStartWith('IDR')
        ->toContain('1,250,000');
});

test('the catalog exposes a photo only when it has alt text', function () {
    Product::factory()->create([
        'name' => 'With Photo',
        'image_path' => 'products/2026/11/photo.webp',
        'image_alt' => 'A robot chassis on a desk.',
        'image_width' => 1024,
        'image_height' => 768,
        'is_published' => true,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'Without Alt',
        'image_path' => 'products/2026/11/other.webp',
        'image_alt' => null,
        'is_published' => true,
        'is_active' => true,
    ]);

    $response = $this->getJson(route('api.v1.catalog'))->assertOk();

    expect($response->json('products.0.image'))
        ->toMatchArray([
            'alt' => 'A robot chassis on a desk.',
            'width' => 1024,
            'height' => 768,
        ])
        ->and($response->json('products.0.image.url'))->toContain('/storage/products/2026/11/photo.webp')
        ->and($response->json('products.1.image'))->toBeNull();
});

test('the catalog lists only active categories', function () {
    ProductCategory::query()->create([
        'name' => 'Visible',
        'slug' => 'visible',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    ProductCategory::query()->create([
        'name' => 'Hidden',
        'slug' => 'hidden',
        'is_active' => false,
    ]);

    $this->getJson(route('api.v1.catalog'))
        ->assertOk()
        ->assertJsonCount(1, 'categories')
        ->assertJsonPath('categories.0', [
            'slug' => 'visible',
            'name' => 'Visible',
            'sort_order' => 2,
        ]);
});

test('the catalog response is cacheable and supports conditional requests', function () {
    Product::factory()->create([
        'name' => 'Cacheable',
        'is_published' => true,
        'is_active' => true,
    ]);

    $first = $this->getJson(route('api.v1.catalog'))
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, public')
        ->assertJsonStructure(['version', 'currency', 'categories', 'products']);

    $etag = $first->headers->get('ETag');

    expect($etag)->not->toBeNull();

    $this->getJson(route('api.v1.catalog'), ['If-None-Match' => $etag])
        ->assertStatus(304);
});
