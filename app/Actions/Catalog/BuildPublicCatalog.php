<?php

namespace App\Actions\Catalog;

use App\Actions\Currencies\ResolveBaseCurrency;
use App\Http\Resources\Api\V1\CatalogCategoryResource;
use App\Http\Resources\Api\V1\CatalogProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class BuildPublicCatalog
{
    public function __construct(private readonly ResolveBaseCurrency $resolveBaseCurrency) {}

    /**
     * Build the flat catalog snapshot consumed by the public website.
     *
     * @return array{
     *     version: string,
     *     currency: array{code: string, symbol: string, decimal_places: int},
     *     categories: array<int, array<string, mixed>>,
     *     products: array<int, array<string, mixed>>
     * }
     */
    public function handle(): array
    {
        $currency = $this->resolveBaseCurrency->handle();

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'sort_order']);

        $products = Product::query()
            ->where('is_published', true)
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('category_id')
                    ->orWhereHas('category', fn (Builder $query): Builder => $query
                        ->where('is_active', true)
                        ->where('is_published', true));
            })
            ->with('category:id,name,slug')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return [
            'version' => $this->version(),
            'currency' => $currency,
            'categories' => CatalogCategoryResource::collection($categories)->resolve(),
            'products' => $products
                ->map(fn (Product $product): array => (new CatalogProductResource($product, $currency))->resolve())
                ->values()
                ->all(),
        ];
    }

    /**
     * A timestamp that changes whenever anything the catalog reads changes.
     *
     * Used for the HTTP ETag now and for skipping redundant site rebuilds later.
     */
    public function version(): string
    {
        $latest = collect([
            Product::query()->max('updated_at'),
            ProductCategory::query()->max('updated_at'),
        ])->filter()->max();

        // An empty catalog has no timestamp. Return a fixed epoch instead of
        // "now" so the version stays stable until the first record exists.
        if ($latest === null) {
            return Carbon::createFromTimestamp(0)->toIso8601String();
        }

        return Carbon::parse($latest)->toIso8601String();
    }
}
