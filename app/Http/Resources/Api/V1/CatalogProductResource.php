<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

/**
 * @mixin Product
 */
class CatalogProductResource extends JsonResource
{
    /**
     * @param  array{code: string, symbol: string, decimal_places: int}  $currency
     */
    public function __construct(Product $product, private readonly array $currency)
    {
        parent::__construct($product);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'type' => $this->type->value,
            'category' => $this->category?->slug,
            'summary' => $this->summary,
            'description' => $this->description,
            'price' => $this->publicPrice(),
            'price_display' => $this->show_price ? 'amount' : 'contact',
            'image' => $this->publicImage(),
            'featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
        ];
    }

    /**
     * The public price, if the item should show one.
     *
     * @return array{amount: float, formatted: string}|null
     */
    private function publicPrice(): ?array
    {
        if (! $this->show_price) {
            return null;
        }

        $amount = (float) $this->price;
        $formatted = Number::currency(
            $amount,
            $this->currency['code'],
            locale: app()->getLocale(),
            precision: $this->currency['decimal_places'],
        );

        return [
            'amount' => $amount,
            'formatted' => $formatted !== false
                ? $formatted
                : $this->currency['symbol'].' '.number_format($amount, $this->currency['decimal_places']),
        ];
    }

    /**
     * The public photo, only when it has alt text to describe it.
     *
     * @return array{url: string, alt: string, width: int|null, height: int|null}|null
     */
    private function publicImage(): ?array
    {
        if (blank($this->image_url) || blank($this->image_alt)) {
            return null;
        }

        return [
            'url' => $this->image_url,
            'alt' => $this->image_alt,
            'width' => $this->image_width,
            'height' => $this->image_height,
        ];
    }
}
