<?php

namespace App\Actions\Orders;

use App\Actions\NumberSequences\GenerateSequenceNumber;
use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CreatePrintOrder
{
    /**
     * Fixed print settings used by the public estimator.
     */
    private const INFILL_PERCENT = 70;

    private const LAYER_HEIGHT_MM = 0.12;

    private const FILAMENT_DIAMETER_MM = 1.75;

    public function __construct(private readonly GenerateSequenceNumber $generateNumber) {}

    /**
     * Create the invoice for a public 3D print order.
     *
     * The submitted idempotency key makes a retried browser request return the
     * first invoice instead of charging the customer twice.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Invoice
    {
        $key = (string) $data['idempotency_key'];

        if ($existing = Invoice::query()->where('idempotency_key', $key)->first()) {
            return $existing;
        }

        try {
            return DB::transaction(fn (): Invoice => $this->create($data));
        } catch (UniqueConstraintViolationException) {
            return Invoice::query()->where('idempotency_key', $key)->firstOrFail();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function create(array $data): Invoice
    {
        $print = $data['print'];
        $price = round((float) $print['price'], 2);

        $customer = Customer::query()->create([
            'name' => $data['customer']['name'],
            'email' => $data['customer']['email'],
            'phone' => $data['customer']['whatsapp'],
            'address' => $this->customerAddress($data),
        ]);

        $invoice = Invoice::query()->create([
            'customer_id' => $customer->id,
            'number' => $this->generateNumber->handle('invoice'),
            'idempotency_key' => $data['idempotency_key'],
            'status' => InvoiceStatus::Sent,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'subtotal' => $price,
            'discount_type' => DiscountType::None,
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $price,
            'notes' => $this->notes($data, $price),
        ]);

        $invoice->items()->create([
            'product_id' => Product::query()->where('slug', 'custom-3d-printing')->value('id'),
            'quantity' => 1,
            'unit_price' => $price,
            'tax_rate' => 0,
        ]);

        return $invoice;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function customerAddress(array $data): ?string
    {
        if ($data['fulfillment'] !== 'shipment') {
            return null;
        }

        $shipping = $data['shipping'] ?? [];

        return collect([
            $shipping['address'] ?? null,
            $shipping['detail'] ?? null,
            $shipping['city'] ?? null,
            $shipping['province'] ?? null,
            $shipping['postal_code'] ?? null,
        ])->filter(fn (mixed $line): bool => filled($line))
            ->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notes(array $data, float $price): string
    {
        $print = $data['print'];
        $lines = [
            'Website order from the 3D print estimator.',
            'Automated estimate — review before printing.',
            '',
        ];

        $lines[] = 'Fulfillment: '.($data['fulfillment'] === 'shipment' ? 'Shipment' : 'Pickup');

        if ($data['fulfillment'] === 'shipment') {
            $shipping = $data['shipping'] ?? [];
            $lines[] = 'Address label: '.$shipping['label'];
            $lines[] = 'Address: '.$shipping['address'];

            if (filled($shipping['detail'] ?? null)) {
                $lines[] = 'Detail: '.$shipping['detail'];
            }

            $lines[] = 'City: '.$shipping['city'];
            $lines[] = 'Province: '.$shipping['province'];
            $lines[] = 'Postal code: '.$shipping['postal_code'];
        }

        $lines[] = '';
        $lines[] = 'Model: '.$data['model']['name'];

        if (filled($data['model']['size'] ?? null)) {
            $lines[] = 'Size: '.$data['model']['size'];
        }

        $lines[] = 'Print: '.$print['filament_name'].', '.$print['filament_color']
            .' — '.number_format((float) $print['weight_g'], 1).' g, '
            .$this->formatTime((float) $print['print_hours']);
        $lines[] = 'Settings: '.self::INFILL_PERCENT.'% infill, '
            .self::LAYER_HEIGHT_MM.' mm layers, '
            .self::FILAMENT_DIAMETER_MM.' mm filament';
        $lines[] = 'Estimate: Rp '.number_format($price, 0, ',', '.');
        $lines[] = 'Payment: QRIS';

        if (filled($data['customer']['note'] ?? null)) {
            $lines[] = '';
            $lines[] = 'Customer note:';
            $lines[] = $data['customer']['note'];
        }

        return implode("\n", $lines);
    }

    private function formatTime(float $hours): string
    {
        $totalMinutes = max(1, (int) round($hours * 60));
        $wholeHours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        if ($wholeHours === 0) {
            return $minutes.'m';
        }

        return $minutes > 0 ? $wholeHours.'h '.$minutes.'m' : $wholeHours.'h';
    }
}
