<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sale_id
 * @property int|null $product_id
 * @property string $description
 * @property string|null $sku
 * @property string $quantity
 * @property int|null $unit_id
 * @property string $unit_factor
 * @property string $unit_price
 * @property string $cost_price
 * @property string $discount_amount
 * @property string $tax_rate
 * @property bool $tax_inclusive
 * @property string $tax_amount
 * @property string $line_total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['sale_id', 'product_id', 'description', 'sku', 'quantity', 'unit_id', 'unit_factor', 'unit_price', 'cost_price', 'discount_amount', 'tax_rate', 'tax_inclusive', 'tax_amount', 'line_total'])]
class SaleItem extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_factor' => 'decimal:6',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:3',
            'tax_inclusive' => 'boolean',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    /**
     * The sale this line belongs to.
     *
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * The product that was sold.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The unit the line was sold in.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * The return lines referencing this sale line.
     *
     * @return HasMany<SaleReturnItem, $this>
     */
    public function returnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
