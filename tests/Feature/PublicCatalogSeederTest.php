<?php

use App\Enums\ProductType;
use App\Models\NumberSequence;
use App\Models\Product;
use App\Models\ProductCategory;
use Database\Seeders\PosDefaultsSeeder;
use Database\Seeders\PublicCatalogSeeder;

beforeEach(function () {
    $this->seed(PosDefaultsSeeder::class);
});

test('the legacy catalog is imported exactly once', function () {
    $this->seed(PublicCatalogSeeder::class);
    $this->seed(PublicCatalogSeeder::class);

    expect(Product::query()->count())->toBe(5);

    $response = $this->getJson(route('api.v1.catalog'))
        ->assertOk()
        ->assertJsonCount(5, 'products');

    expect($response->json('products.*.slug'))->toBe([
        'robotics-learning-kit',
        'connected-medication',
        'medication-companion',
        'custom-3d-printing',
        'operations-management',
    ])->and($response->json('products.*.category'))->toBe([
        'education-robotics-kit',
        'medication-iot',
        'medication-android-app',
        '3d-printing',
        'management-software',
    ])->and($response->json('products.*.price_display'))->each->toBe('contact');
});

test('the existing 3D printing category is reused, not duplicated or renamed', function () {
    $existing = ProductCategory::query()->create([
        'name' => '3D Printing',
        'slug' => '3d-printing',
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $this->seed(PublicCatalogSeeder::class);

    $product = Product::query()->where('slug', 'custom-3d-printing')->firstOrFail();

    expect(ProductCategory::query()->where('slug', '3d-printing')->count())->toBe(1)
        ->and($existing->fresh()->sort_order)->toBe(0)
        ->and($product->category_id)->toBe($existing->id);
});

test('only 3D printing is a service and only the robotics kit is featured', function () {
    $this->seed(PublicCatalogSeeder::class);

    expect(Product::query()->where('type', ProductType::Service->value)->pluck('slug')->all())
        ->toBe(['custom-3d-printing'])
        ->and(Product::query()->where('is_featured', true)->pluck('slug')->all())
        ->toBe(['robotics-learning-kit'])
        ->and(Product::query()->where('show_price', true)->count())->toBe(0)
        ->and(Product::query()->where('track_stock', true)->count())->toBe(0)
        ->and(Product::query()->where('price', '>', 0)->count())->toBe(0);
});

test('re-seeding never overwrites an edited product', function () {
    $this->seed(PublicCatalogSeeder::class);

    $product = Product::query()->where('slug', 'robotics-learning-kit')->firstOrFail();
    $product->update(['summary' => 'Custom summary from the admin.']);

    $this->seed(PublicCatalogSeeder::class);

    expect($product->fresh()->summary)->toBe('Custom summary from the admin.')
        ->and(Product::query()->count())->toBe(5);
});

test('it fails loudly when the product SKU sequence is missing', function () {
    NumberSequence::query()->where('key', 'product')->delete();

    expect(fn () => $this->seed(PublicCatalogSeeder::class))
        ->toThrow(RuntimeException::class);
});
