<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
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
    private function periodStart(string $range): Carbon
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
    private function summary(Carbon $from, Carbon $to): array
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

            $day->addDay();
        }

        return $points;
    }

    /**
     * The products that produced the most revenue in the period.
     *
     * @return array<int, array{id: int, name: string, quantity: float, revenue: float}>
     */
    private function topProducts(Carbon $from, Carbon $to): array
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
    private function topCustomers(Carbon $from, Carbon $to): array
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
                        : (int) $dueDate->copy()->startOfDay()->diffInDays(today()),
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
