<?php

namespace App\Http\Controllers;

use App\Enums\CashMovementType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\Shift;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The selectable reporting ranges.
     *
     * @var array<string, string>
     */
    private const RANGES = [
        'today' => 'Today',
        '7d' => '7 days',
        '30d' => '30 days',
        'month' => 'This month',
    ];

    /**
     * Show the business overview.
     */
    public function __invoke(Request $request): Response
    {
        $range = (string) $request->query('range', '30d');

        if (! array_key_exists($range, self::RANGES)) {
            $range = '30d';
        }

        $from = $this->periodStart($range);
        $to = today();

        return Inertia::render('Dashboard', [
            'range' => $range,
            'ranges' => $this->ranges(),
            'summary' => $this->summary($from, $to),
            'counter' => $this->counter(),
            'lowStock' => $this->lowStock(),
            'trend' => $this->trend($range),
            'topProducts' => $this->topProducts($from, $to),
            'topCustomers' => $this->topCustomers($from, $to),
            'overdueInvoices' => $this->overdueInvoices(),
            'draftCount' => Invoice::query()->where('status', InvoiceStatus::Draft->value)->count(),
            'recentInvoices' => $this->recentInvoices(),
            'statuses' => $this->statuses(),
        ]);
    }

    /**
     * The first day of the selected range.
     */
    private function periodStart(string $range): CarbonInterface
    {
        return match ($range) {
            'today' => today(),
            '7d' => today()->subDays(6),
            'month' => today()->startOfMonth(),
            default => today()->subDays(29),
        };
    }

    /**
     * Billed, outstanding, and tax totals for the period.
     *
     * Receivables are a live balance, so they ignore the selected range.
     *
     * @return array{
     *     billed: array{total: float, net: float, tax: float, count: int, average: float},
     *     issued: int,
     *     outstanding: array{total: float, count: int, overdue_total: float, overdue_count: int}
     * }
     */
    private function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $billable = [InvoiceStatus::Sent->value, InvoiceStatus::Paid->value];
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        $billed = $this->aggregate(DB::table('invoices')
            ->whereIn('status', $billable)
            ->whereDate('issue_date', '>=', $fromDate)
            ->whereDate('issue_date', '<=', $toDate)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total, COALESCE(SUM(subtotal - discount_amount), 0) as net, COALESCE(SUM(tax_amount), 0) as tax')
            ->first());

        $outstanding = $this->aggregate(DB::table('invoices')
            ->where('status', InvoiceStatus::Sent->value)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->first());

        $overdue = $this->aggregate(DB::table('invoices')
            ->where('status', InvoiceStatus::Sent->value)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today()->toDateString())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->first());

        $count = (int) ($billed['count'] ?? 0);
        $total = (float) ($billed['total'] ?? 0);

        return [
            'billed' => [
                'total' => $total,
                'net' => (float) ($billed['net'] ?? 0),
                'tax' => (float) ($billed['tax'] ?? 0),
                'count' => $count,
                'average' => $count > 0 ? round($total / $count, 2) : 0.0,
            ],
            'issued' => DB::table('invoices')
                ->whereDate('issue_date', '>=', $fromDate)
                ->whereDate('issue_date', '<=', $toDate)
                ->count(),
            'outstanding' => [
                'total' => (float) ($outstanding['total'] ?? 0),
                'count' => (int) ($outstanding['count'] ?? 0),
                'overdue_total' => (float) ($overdue['total'] ?? 0),
                'overdue_count' => (int) ($overdue['count'] ?? 0),
            ],
        ];
    }

    /**
     * Daily billed revenue for the chart.
     *
     * A single day is not a useful chart, so today's range shows the last week.
     * Rows are bucketed in PHP because SQLite keeps a time part on date casts
     * while PostgreSQL stores a plain date.
     *
     * @return array<int, array{date: string, total: float}>
     */
    private function trend(string $range): array
    {
        $start = match ($range) {
            'today', '7d' => today()->subDays(6),
            'month' => today()->startOfMonth(),
            default => today()->subDays(29),
        };

        $totals = [];

        $rows = DB::table('invoices')
            ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Paid->value])
            ->whereDate('issue_date', '>=', $start->toDateString())
            ->whereDate('issue_date', '<=', today()->toDateString())
            ->get(['issue_date', 'total_amount']);

        foreach ($rows as $row) {
            $data = (array) $row;
            $date = Carbon::parse((string) $data['issue_date'])->toDateString();

            $totals[$date] = ($totals[$date] ?? 0) + (float) $data['total_amount'];
        }

        $points = [];
        $day = $start->copy();

        while ($day->lte(today())) {
            $date = $day->toDateString();

            $points[] = [
                'date' => $date,
                'total' => round($totals[$date] ?? 0, 2),
            ];

            $day = $day->addDay();
        }

        return $points;
    }

    /**
     * The products that produced the most revenue in the period.
     *
     * @return array<int, array{id: int, name: string, quantity: float, revenue: float}>
     */
    private function topProducts(CarbonInterface $from, CarbonInterface $to): array
    {
        return DB::table('invoice_items')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->whereIn('invoices.status', [InvoiceStatus::Sent->value, InvoiceStatus::Paid->value])
            ->whereDate('invoices.issue_date', '>=', $from->toDateString())
            ->whereDate('invoices.issue_date', '<=', $to->toDateString())
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->selectRaw('products.id as id, products.name as name, SUM(invoice_items.quantity) as quantity, SUM(invoice_items.quantity * invoice_items.unit_price) as revenue')
            ->get()
            ->map(function (object $row): array {
                $data = (array) $row;

                return [
                    'id' => (int) $data['id'],
                    'name' => (string) $data['name'],
                    'quantity' => (float) $data['quantity'],
                    'revenue' => (float) $data['revenue'],
                ];
            })
            ->all();
    }

    /**
     * The customers that were billed the most in the period.
     *
     * @return array<int, array{id: int, name: string, invoice_count: int, revenue: float}>
     */
    private function topCustomers(CarbonInterface $from, CarbonInterface $to): array
    {
        return DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->whereIn('invoices.status', [InvoiceStatus::Sent->value, InvoiceStatus::Paid->value])
            ->whereDate('invoices.issue_date', '>=', $from->toDateString())
            ->whereDate('invoices.issue_date', '<=', $to->toDateString())
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->selectRaw('customers.id as id, customers.name as name, COUNT(*) as invoice_count, SUM(invoices.total_amount) as revenue')
            ->get()
            ->map(function (object $row): array {
                $data = (array) $row;

                return [
                    'id' => (int) $data['id'],
                    'name' => (string) $data['name'],
                    'invoice_count' => (int) $data['invoice_count'],
                    'revenue' => (float) $data['revenue'],
                ];
            })
            ->all();
    }

    /**
     * The oldest unpaid invoices that are past their due date.
     *
     * @return array<int, array{id: int, number: string, customer: string|null, due_date: string|null, days_overdue: int, total: float}>
     */
    private function overdueInvoices(): array
    {
        return Invoice::query()
            ->with('customer:id,name')
            ->where('status', InvoiceStatus::Sent->value)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today()->toDateString())
            ->orderBy('due_date')
            ->limit(5)
            ->get(['id', 'number', 'customer_id', 'due_date', 'total_amount'])
            ->map(function (Invoice $invoice): array {
                $dueDate = $invoice->due_date;

                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'customer' => $invoice->customer?->name,
                    'due_date' => $dueDate?->toDateString(),
                    'days_overdue' => $dueDate === null
                        ? 0
                        : (int) abs($dueDate->copy()->startOfDay()->diffInDays(today())),
                    'total' => (float) $invoice->total_amount,
                ];
            })
            ->all();
    }

    /**
     * The newest invoices, regardless of status.
     *
     * @return array<int, array{id: int, number: string, customer: string|null, status: string, issue_date: string, total: float}>
     */
    private function recentInvoices(): array
    {
        return Invoice::query()
            ->with('customer:id,name')
            ->latest('issue_date')
            ->latest('id')
            ->limit(6)
            ->get(['id', 'number', 'customer_id', 'status', 'issue_date', 'total_amount'])
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'customer' => $invoice->customer?->name,
                'status' => $invoice->status->value,
                'issue_date' => $invoice->issue_date->toDateString(),
                'total' => (float) $invoice->total_amount,
            ])
            ->all();
    }

    /**
     * Counter (POS) activity for today, plus the shift that is currently open.
     *
     * @return array{
     *     has_sales: bool,
     *     count: int,
     *     total: float,
     *     average: float,
     *     hourly: array<int, array{hour: int, total: float}>,
     *     open_shift: array{register: string|null, opened_by: string|null, opened_at: string, opening_float: float, cash_sales: float, expected_cash: float}|null
     * }
     */
    private function counter(): array
    {
        $todaySales = DB::table('sales')
            ->where('status', SaleStatus::Completed->value)
            ->whereDate('completed_at', today()->toDateString());

        $summary = $this->aggregate((clone $todaySales)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first());

        $count = (int) ($summary['count'] ?? 0);
        $total = (float) ($summary['total'] ?? 0);

        return [
            'has_sales' => DB::table('sales')
                ->where('status', SaleStatus::Completed->value)
                ->exists(),
            'count' => $count,
            'total' => $total,
            'average' => $count > 0 ? round($total / $count, 2) : 0.0,
            'hourly' => $this->hourlyTakings($todaySales),
            'open_shift' => $this->openShift(),
        ];
    }

    /**
     * Bucket today's completed sales into 24 hourly totals.
     *
     * @return array<int, array{hour: int, total: float}>
     */
    private function hourlyTakings(Builder $sales): array
    {
        $hourly = array_fill(0, 24, 0.0);

        foreach ($sales->get(['completed_at', 'total']) as $row) {
            $data = (array) $row;
            $hour = (int) Carbon::parse((string) $data['completed_at'])->format('G');

            $hourly[$hour] += (float) $data['total'];
        }

        $points = [];

        foreach ($hourly as $hour => $total) {
            $points[] = [
                'hour' => $hour,
                'total' => round($total, 2),
            ];
        }

        return $points;
    }

    /**
     * The shift that is currently open, with its expected cash on hand.
     *
     * @return array{register: string|null, opened_by: string|null, opened_at: string, opening_float: float, cash_sales: float, expected_cash: float}|null
     */
    private function openShift(): ?array
    {
        $shift = Shift::query()
            ->with(['register:id,name', 'user:id,name'])
            ->where('status', ShiftStatus::Open->value)
            ->latest('opened_at')
            ->first();

        if ($shift === null) {
            return null;
        }

        $cashSales = (float) DB::table('payments')
            ->join('sales', function (JoinClause $join): void {
                $join->on('sales.id', '=', 'payments.payable_id')
                    ->where('payments.payable_type', Sale::class);
            })
            ->join('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->where('sales.shift_id', $shift->id)
            ->where('payments.type', PaymentType::Payment->value)
            ->where('payment_methods.is_cash', true)
            ->sum('payments.amount');

        $cashIn = (float) DB::table('cash_movements')
            ->where('shift_id', $shift->id)
            ->where('type', CashMovementType::In->value)
            ->sum('amount');

        $cashOut = (float) DB::table('cash_movements')
            ->where('shift_id', $shift->id)
            ->where('type', CashMovementType::Out->value)
            ->sum('amount');

        return [
            'register' => $shift->register?->name,
            'opened_by' => $shift->user?->name,
            'opened_at' => $shift->opened_at->toIso8601String(),
            'opening_float' => (float) $shift->opening_float,
            'cash_sales' => $cashSales,
            'expected_cash' => round((float) $shift->opening_float + $cashSales + $cashIn - $cashOut, 2),
        ];
    }

    /**
     * Tracked products that are at or below their reorder point.
     *
     * Only products that actually have a stock balance are considered, so a
     * fresh catalogue is not reported as entirely out of stock.
     *
     * @return array{has_data: bool, items: array<int, array{id: int, name: string, sku: string, quantity: float, reorder_point: float}>}
     */
    private function lowStock(): array
    {
        $items = DB::table('stock_levels')
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->where('products.is_active', true)
            ->where('products.track_stock', true)
            ->whereNotNull('stock_levels.reorder_point')
            ->whereColumn('stock_levels.quantity_on_hand', '<=', 'stock_levels.reorder_point')
            ->orderBy('stock_levels.quantity_on_hand')
            ->limit(5)
            ->selectRaw('products.id as id, products.name as name, products.sku as sku, stock_levels.quantity_on_hand as quantity, stock_levels.reorder_point as reorder_point')
            ->get()
            ->map(function (object $row): array {
                $data = (array) $row;

                return [
                    'id' => (int) $data['id'],
                    'name' => (string) $data['name'],
                    'sku' => (string) $data['sku'],
                    'quantity' => (float) $data['quantity'],
                    'reorder_point' => (float) $data['reorder_point'],
                ];
            })
            ->all();

        return [
            'has_data' => DB::table('stock_levels')->exists(),
            'items' => $items,
        ];
    }

    /**
     * Normalise an aggregate row into an array.
     *
     * @return array<string, mixed>
     */
    private function aggregate(?object $row): array
    {
        return $row === null ? [] : (array) $row;
    }

    /**
     * The selectable ranges, shaped for the interface.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function ranges(): array
    {
        $ranges = [];

        foreach (self::RANGES as $value => $label) {
            $ranges[] = ['value' => $value, 'label' => $label];
        }

        return $ranges;
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
}
