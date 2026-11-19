<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $number
 * @property string|null $idempotency_key
 * @property InvoiceStatus $status
 * @property OrderStatus $order_status
 * @property string|null $fulfillment
 * @property string|null $model_path
 * @property string|null $model_name
 * @property string|null $model_mime
 * @property int|null $model_size
 * @property Carbon $issue_date
 * @property Carbon|null $due_date
 * @property string $subtotal
 * @property DiscountType $discount_type
 * @property string $discount_value
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total_amount
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['customer_id', 'number', 'idempotency_key', 'status', 'order_status', 'fulfillment', 'model_path', 'model_name', 'model_mime', 'model_size', 'issue_date', 'due_date', 'subtotal', 'discount_type', 'discount_value', 'discount_amount', 'tax_amount', 'total_amount', 'notes'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = ['order_status_label'];

    /**
     * The private storage path is never sent to the frontend.
     *
     * @var list<string>
     */
    protected $hidden = ['model_path'];

    /**
     * Delete the stored model when its invoice is deleted.
     */
    protected static function booted(): void
    {
        static::deleted(function (Invoice $invoice): void {
            if (filled($invoice->model_path)) {
                Storage::disk('local')->delete($invoice->model_path);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'order_status' => OrderStatus::class,
            'model_size' => 'integer',
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * The human-readable progress label for this invoice.
     */
    protected function orderStatusLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->order_status instanceof OrderStatus
            ? $this->order_status->label($this->fulfillment)
            : __('invoice.order_statuses.sent'));
    }

    /**
     * The customer the invoice is billed to.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The line items on the invoice.
     *
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
