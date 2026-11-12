<?php

namespace App\Observers;

use App\Jobs\RequestCatalogRebuild;
use App\Models\Product;

class ProductObserver
{
    /**
     * Attributes that change what the public catalog renders.
     *
     * Stock, cost, SKU and tax changes must not trigger a website rebuild.
     *
     * @var list<string>
     */
    public const CATALOG_FIELDS = [
        'category_id',
        'name',
        'slug',
        'type',
        'price',
        'description',
        'summary',
        'image_path',
        'image_alt',
        'image_width',
        'image_height',
        'sort_order',
        'is_published',
        'is_featured',
        'show_price',
        'is_active',
    ];

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $this->requestRebuild();
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        if ($product->wasChanged(self::CATALOG_FIELDS)) {
            $this->requestRebuild();
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        $this->requestRebuild();
    }

    private function requestRebuild(): void
    {
        RequestCatalogRebuild::dispatch()
            ->delay(now()->addMinutes((int) config('catalog.rebuild_delay_minutes')));
    }
}
