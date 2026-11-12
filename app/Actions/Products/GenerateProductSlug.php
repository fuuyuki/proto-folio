<?php

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Support\Str;

class GenerateProductSlug
{
    /**
     * Build a unique, URL-safe slug from the product name.
     *
     * Slugs are only generated once, when a product is created: renaming a
     * product must not break links that already point at its public page.
     */
    public function handle(string $name): string
    {
        $base = Str::limit(Str::slug($name), 110, '');
        $base = $base !== '' ? $base : 'product';

        $slug = $base;
        $suffix = 2;

        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
