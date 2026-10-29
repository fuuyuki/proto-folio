<?php

namespace App\Http\Controllers;

use App\Actions\NumberSequences\GenerateSequenceNumber;
use App\Enums\InvoiceStatus;
use App\Http\Requests\InvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the invoices.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $query = Invoice::query()
            ->with('customer:id,name')
            ->withCount('items');

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->whereLike('number', "%{$search}%")
                    ->orWhereHas('customer', function ($query) use ($search): void {
                        $query->whereLike('name', "%{$search}%");
                    });
            });
        }

        if (InvoiceStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        return Inertia::render('invoices/Index', [
            'invoices' => $query->latest('issue_date')->latest('id')->paginate(10)->withQueryString(),
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => $this->statuses(),
        ]);
    }

    /**
     * Show the form for creating an invoice.
     */
    public function create(): Response
    {
        return Inertia::render('invoices/Create', $this->formOptions());
    }

    /**
     * Store a newly created invoice.
     */
    public function store(InvoiceRequest $request, GenerateSequenceNumber $generateNumber): RedirectResponse
    {
        $data = $request->validated();
        $items = $this->normalizeItems($data['items']);

        unset($data['items']);

        $invoice = DB::transaction(function () use ($data, $items, $generateNumber): Invoice {
            $invoice = Invoice::create([
                ...$data,
                'number' => $generateNumber->handle('invoice'),
                ...$this->totals($items),
            ]);

            $invoice->items()->createMany($items);

            return $invoice;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice created.')]);

        return to_route('invoices.show', $invoice);
    }

    /**
     * Display the given invoice.
     */
    public function show(Invoice $invoice): Response
    {
        $invoice->load(['customer:id,name,email,phone,address', 'items.product:id,name,sku']);

        return Inertia::render('invoices/Show', [
            'invoice' => $invoice,
            'items' => $this->presentItems($invoice),
            'labels' => $this->invoiceLabels(),
            'statuses' => $this->statuses(),
        ]);
    }

    /**
     * Show the form for editing the given invoice.
     */
    public function edit(Invoice $invoice): Response
    {
        $invoice->load('items');

        return Inertia::render('invoices/Edit', [
            'invoice' => $invoice,
            'items' => $invoice->items->map(fn (InvoiceItem $item): array => [
                'product_id' => $item->product_id,
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'tax_rate' => (string) $item->tax_rate,
            ])->values(),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the given invoice.
     */
    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validated();
        $items = $this->normalizeItems($data['items']);

        unset($data['items']);

        DB::transaction(function () use ($invoice, $data, $items): void {
            $invoice->update([
                ...$data,
                ...$this->totals($items),
            ]);

            $invoice->items()->delete();
            $invoice->items()->createMany($items);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice updated.')]);

        return to_route('invoices.show', $invoice);
    }

    /**
     * Delete the given invoice.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice deleted.')]);

        return to_route('invoices.index');
    }

    /**
     * Display the 58mm thermal receipt for the given invoice.
     */
    public function print(Invoice $invoice): Response
    {
        $invoice->load(['customer:id,name,email,phone,address', 'items.product:id,name,sku']);

        return Inertia::render('invoices/Print', [
            'invoice' => $invoice,
            'items' => $this->presentItems($invoice),
            'labels' => $this->invoiceLabels(),
            'company' => config('app.name'),
        ]);
    }

    /**
     * Normalize the submitted line items before saving.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        return array_values(array_map(fn (array $item): array => [
            'product_id' => $item['product_id'] ?? null,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'tax_rate' => $item['tax_rate'] ?? 0,
        ], $items));
    }

    /**
     * Calculate the invoice totals from its line items.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, tax_amount: float, total_amount: float}
     */
    private function totals(array $items): array
    {
        $subtotal = 0.0;
        $taxAmount = 0.0;

        foreach ($items as $item) {
            $lineTotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);

            $subtotal += $lineTotal;
            $taxAmount += round($lineTotal * (float) $item['tax_rate'] / 100, 2);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($subtotal + $taxAmount, 2),
        ];
    }

    /**
     * Shape the line items for the show and receipt pages.
     *
     * @return array<int, array<string, mixed>>
     */
    private function presentItems(Invoice $invoice): array
    {
        return $invoice->items->map(function (InvoiceItem $item): array {
            $product = $item->getRelation('product');

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'description' => $product instanceof Product ? $product->name : __('Item'),
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'tax_rate' => (string) $item->tax_rate,
                'line_total' => number_format(
                    (float) $item->quantity * (float) $item->unit_price,
                    2,
                    '.',
                    '',
                ),
            ];
        })->values()->all();
    }

    /**
     * The translated labels used by the invoice pages.
     *
     * @return array<string, string>
     */
    private function invoiceLabels(): array
    {
        return [
            'receipt_title' => __('invoice.receipt_title'),
            'date' => __('invoice.date'),
            'due_date' => __('invoice.due_date'),
            'status' => __('invoice.status'),
            'billed_to' => __('invoice.billed_to'),
            'details' => __('invoice.details'),
            'item' => __('invoice.item'),
            'qty' => __('invoice.qty'),
            'price' => __('invoice.price'),
            'tax' => __('invoice.tax'),
            'amount' => __('invoice.amount'),
            'subtotal' => __('invoice.subtotal'),
            'total' => __('invoice.total'),
            'notes' => __('invoice.notes'),
            'thank_you' => __('invoice.thank_you'),
            'issued' => __('invoice.issued'),
            'print' => __('invoice.print'),
            'print_receipt' => __('invoice.print_receipt'),
            'back_to_invoice' => __('invoice.back_to_invoice'),
        ];
    }

    /**
     * The invoice statuses with their translated labels.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return array_map(
            fn (InvoiceStatus $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            InvoiceStatus::cases(),
        );
    }

    /**
     * The select options shared by the invoice form.
     *
     * @return array{customers: mixed, products: mixed}
     */
    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'products' => Product::query()
                ->where('is_active', true)
                ->with('taxRate:id,rate')
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'price', 'tax_rate_id'])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => (string) $product->price,
                    'tax_rate' => (float) ($product->taxRate->rate ?? 0),
                ])
                ->values(),
        ];
    }
}
