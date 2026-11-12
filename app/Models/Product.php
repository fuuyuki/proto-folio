<?php

namespace App\Models;

use App\Actions\Products\GenerateProductSlug;
use App\Enums\ProductType;
use App\Observers\ProductObserver;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $category_id
 * @property int|null $unit_id
 * @property int|null $tax_rate_id
 * @property string $name
 * @property string $slug
 * @property string $sku
 * @property string|null $barcode
 * @property ProductType $type
 * @property string $price
 * @property string $cost_price
 * @property string|null $description
 * @property string|null $summary
 * @property string|null $image_path
 * @property string|null $image_url
 * @property string|null $image_alt
 * @property int|null $image_width
 * @property int|null $image_height
 * @property int $sort_order
 * @property bool $is_published
 * @property bool $is_featured
 * @property bool $show_price
 * @property bool $track_stock
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['category_id', 'unit_id', 'tax_rate_id', 'name', 'slug', 'sku', 'barcode', 'type', 'price', 'cost_price', 'description', 'summary', 'image_path', 'image_alt', 'image_width', 'image_height', 'sort_order', 'is_published', 'is_featured', 'show_price', 'track_stock', 'is_active'])]
#[ObservedBy([ProductObserver::class])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = ['image_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'image_width' => 'integer',
            'image_height' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'show_price' => 'boolean',
            'track_stock' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Assign a public slug the first time a product is persisted.
     */
    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (blank($product->slug)) {
                $product->slug = app(GenerateProductSlug::class)->handle((string) $product->name);
            }
        });
    }

    /**
     * The public URL of the product photo, if one was uploaded.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => filled($this->image_path)
            ? Storage::disk('public')->url($this->image_path)
            : null);
    }

    /**
     * The category this product belongs to.
     *
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * The unit this product is sold in.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * The default tax rate for this product.
     *
     * @return BelongsTo<TaxRate, $this>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * The current stock balances for this product.
     *
     * @return HasMany<StockLevel, $this>
     */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /**
     * The stock movements recorded for this product.
     *
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * The sale lines that reference this product.
     *
     * @return HasMany<SaleItem, $this>
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * The invoice line items referencing this product.
     *
     * @return HasMany<InvoiceItem, $this>
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
