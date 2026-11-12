<?php

namespace App\Observers;

use App\Jobs\RequestCatalogRebuild;
use App\Models\ProductCategory;

class ProductCategoryObserver
{
    /**
     * Attributes that change what the public catalog renders.
     *
     * @var list<string>
     */
    public const CATALOG_FIELDS = [
        'name',
        'slug',
        'sort_order',
        'is_active',
        'is_published',
        'parent_id',
    ];

    /**
     * Handle the ProductCategory "created" event.
     */
    public function created(ProductCategory $category): void
    {
        $this->requestRebuild();
    }

    /**
     * Handle the ProductCategory "updated" event.
     */
    public function updated(ProductCategory $category): void
    {
        if ($category->wasChanged(self::CATALOG_FIELDS)) {
            $this->requestRebuild();
        }
    }

    /**
     * Handle the ProductCategory "deleted" event.
     */
    public function deleted(ProductCategory $category): void
    {
        $this->requestRebuild();
    }

    private function requestRebuild(): void
    {
        RequestCatalogRebuild::dispatch()
            ->delay(now()->addMinutes((int) config('catalog.rebuild_delay_minutes')));
    }
}
