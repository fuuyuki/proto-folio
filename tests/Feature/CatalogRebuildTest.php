<?php

use App\Actions\Catalog\BuildPublicCatalog;
use App\Jobs\RequestCatalogRebuild;
use App\Models\Product;
use App\Models\ProductCategory;
use Database\Seeders\PosDefaultsSeeder;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(PosDefaultsSeeder::class);

    Queue::fake();

    config([
        'catalog.rebuild_url' => 'https://protofylabs.test/api/rebuild',
        'catalog.rebuild_token' => 'rebuild-secret',
        'catalog.rebuild_delay_minutes' => 2,
    ]);
});

test('creating a product queues a site rebuild', function () {
    Product::factory()->create(['is_published' => false]);

    Queue::assertPushed(RequestCatalogRebuild::class);
});

test('editing public product fields queues a site rebuild', function () {
    $product = Product::factory()->create();

    $product->update(['summary' => 'A freshly written summary.']);

    Queue::assertPushed(RequestCatalogRebuild::class);
});

test('publishing a product queues a site rebuild', function () {
    $product = Product::factory()->create(['is_published' => false]);

    $product->update(['is_published' => true]);

    Queue::assertPushed(RequestCatalogRebuild::class);
});

test('internal product changes never queue a site rebuild', function () {
    $product = Product::factory()->create();

    Queue::fake();

    $product->update([
        'sku' => 'INTERNAL-SKU',
        'barcode' => '8991234567890',
        'cost_price' => '12.50',
        'track_stock' => true,
    ]);

    Queue::assertNotPushed(RequestCatalogRebuild::class);
});

test('deleting a product queues a site rebuild', function () {
    $product = Product::factory()->create();

    $product->delete();

    Queue::assertPushed(RequestCatalogRebuild::class);
});

test('editing a category queues a site rebuild', function () {
    $category = ProductCategory::query()->create([
        'name' => 'Workshop',
        'slug' => 'workshop',
        'is_active' => true,
    ]);

    $category->update(['is_active' => false]);

    Queue::assertPushed(RequestCatalogRebuild::class);
});

test('the rebuild job sends the catalog version with the configured token', function () {
    Http::fake();

    $product = Product::factory()->create();
    $version = app(BuildPublicCatalog::class)->version();

    Cache::put('public-catalog:v1', ['stale' => true]);

    (new RequestCatalogRebuild('product changed'))->handle(app(BuildPublicCatalog::class));

    Http::assertSent(function (ClientRequest $request) use ($version): bool {
        return $request->url() === 'https://protofylabs.test/api/rebuild'
            && $request->hasHeader('Authorization', 'Bearer rebuild-secret')
            && $request['version'] === $version
            && $request['reason'] === 'product changed';
    });

    expect(Cache::has('public-catalog:v1'))->toBeFalse()
        ->and(Cache::get('catalog:rebuild-requested-version'))->toBe($version);
});

test('the rebuild job clears the cached catalog snapshot', function () {
    Http::fake();

    Product::factory()->create();

    Cache::put('public-catalog:v1', ['stale' => true]);

    (new RequestCatalogRebuild)->handle(app(BuildPublicCatalog::class));

    expect(Cache::has('public-catalog:v1'))->toBeFalse();
});

test('the rebuild job does nothing without a configured endpoint', function () {
    Http::fake();

    config(['catalog.rebuild_url' => null, 'catalog.rebuild_token' => null]);

    Product::factory()->create();

    Cache::put('public-catalog:v1', ['fresh' => true]);

    (new RequestCatalogRebuild)->handle(app(BuildPublicCatalog::class));

    Http::assertNothingSent();

    expect(Cache::has('public-catalog:v1'))->toBeTrue();
});

test('the rebuild job skips a version that was already requested', function () {
    Http::fake();

    Product::factory()->create();
    $version = app(BuildPublicCatalog::class)->version();

    Cache::forever('catalog:rebuild-requested-version', $version);

    (new RequestCatalogRebuild)->handle(app(BuildPublicCatalog::class));

    Http::assertNothingSent();
});

test('the rebuild job fails loudly when the endpoint rejects the request', function () {
    Http::fake([
        'https://protofylabs.test/api/rebuild' => Http::response('nope', 500),
    ]);

    Product::factory()->create();

    expect(fn () => (new RequestCatalogRebuild)->handle(app(BuildPublicCatalog::class)))
        ->toThrow(RuntimeException::class);

    expect(Cache::has('catalog:rebuild-requested-version'))->toBeFalse();
});

test('the version endpoint returns the current catalog version uncached', function () {
    Product::factory()->create();

    $version = app(BuildPublicCatalog::class)->version();

    $this->getJson(route('api.v1.catalog.version'))
        ->assertOk()
        ->assertExactJson(['version' => $version]);

    expect($this->getJson(route('api.v1.catalog.version'))->headers->get('Cache-Control'))
        ->toContain('no-store');
});

test('an empty catalog reports a stable version', function () {
    $version = app(BuildPublicCatalog::class)->version();

    expect($version)->toBe(app(BuildPublicCatalog::class)->version());
});
