<script module lang="ts">
    import { dashboard as dashboardRoute } from '@/routes';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboardRoute(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import Clock from '@lucide/svelte/icons/clock';
    import Landmark from '@lucide/svelte/icons/landmark';
    import Plus from '@lucide/svelte/icons/plus';
    import ReceiptText from '@lucide/svelte/icons/receipt-text';
    import TrendingUp from '@lucide/svelte/icons/trending-up';
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import InvoiceStatusBadge from '@/components/InvoiceStatusBadge.svelte';
    import RevenueTrend from '@/components/dashboard/RevenueTrend.svelte';
    import { Button } from '@/components/ui/button';
    import { formatMoney } from '@/lib/currency';
    import { dashboard } from '@/routes';
    import { create, index, show } from '@/routes/invoices';
    import type { InvoiceStatus } from '@/types';

    type RangeOption = { value: string; label: string };

    type TrendPoint = { date: string; total: number };

    type Summary = {
        billed: {
            total: number;
            net: number;
            tax: number;
            count: number;
            average: number;
        };
        issued: number;
        outstanding: {
            total: number;
            count: number;
            overdue_total: number;
            overdue_count: number;
        };
    };

    type ProductRow = {
        id: number;
        name: string;
        quantity: number;
        revenue: number;
    };

    type CustomerRow = {
        id: number;
        name: string;
        invoice_count: number;
        revenue: number;
    };

    type OverdueInvoice = {
        id: number;
        number: string;
        customer: string | null;
        due_date: string | null;
        days_overdue: number;
        total: number;
    };

    type RecentInvoice = {
        id: number;
        number: string;
        customer: string | null;
        status: InvoiceStatus;
        issue_date: string;
        total: number;
    };

    let {
        range,
        ranges,
        summary,
        trend,
        topProducts,
        topCustomers,
        overdueInvoices,
        draftCount,
        recentInvoices,
        statuses,
    }: {
        range: string;
        ranges: RangeOption[];
        summary: Summary;
        trend: TrendPoint[];
        topProducts: ProductRow[];
        topCustomers: CustomerRow[];
        overdueInvoices: OverdueInvoice[];
        draftCount: number;
        recentInvoices: RecentInvoice[];
        statuses: { value: InvoiceStatus; label: string }[];
    } = $props();

    const trendCaption = $derived(
        range === 'month'
            ? 'This month'
            : range === 'today' || range === '7d'
              ? 'Last 7 days'
              : 'Last 30 days',
    );

    const trendTotal = $derived(
        trend.reduce((sum, point) => sum + point.total, 0),
    );

    function selectRange(value: string): void {
        if (value === range) {
            return;
        }

        router.get(
            dashboard().url,
            { range: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function statusLabel(status: InvoiceStatus): string {
        return statuses.find((item) => item.value === status)?.label ?? status;
    }

    function formatDate(value: string | null): string {
        if (!value) {
            return '—';
        }

        return new Date(`${value}T00:00:00`).toLocaleDateString(
            page.props.locale,
            { day: '2-digit', month: 'short', year: 'numeric' },
        );
    }

    function formatQuantity(value: number): string {
        return value.toLocaleString(page.props.locale, {
            maximumFractionDigits: 3,
        });
    }
</script>

<AppHead title="Dashboard" />

<AppPage
    title="Dashboard"
    description="Billed revenue, money owed, and what needs attention."
>
    {#snippet actions()}
        <div class="flex flex-wrap items-center gap-2">
            <div
                class="flex items-center gap-1 rounded-lg border border-border bg-card p-1"
            >
                {#each ranges as option (option.value)}
                    <button
                        type="button"
                        class="rounded-md px-3 py-1 text-xs font-medium transition-colors {option.value ===
                        range
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-row-hover hover:text-foreground'}"
                        aria-pressed={option.value === range}
                        onclick={() => selectRange(option.value)}
                    >
                        {option.label}
                    </button>
                {/each}
            </div>

            <Button asChild>
                {#snippet children(props)}
                    <Link {...props} href={create().url} class={props.class}>
                        <Plus class="size-4" />
                        New invoice
                    </Link>
                {/snippet}
            </Button>
        </div>
    {/snippet}

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <section class="rounded-xl border border-border bg-card p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Billed
                    </p>
                    <p class="font-mono text-2xl font-semibold">
                        {formatMoney(summary.billed.total)}
                    </p>
                </div>
                <TrendingUp class="size-4 shrink-0 text-muted-foreground" />
            </div>
            <p class="mt-3 text-xs text-muted-foreground">
                {summary.billed.count} invoice{summary.billed.count === 1
                    ? ''
                    : 's'} · net {formatMoney(summary.billed.net)}
            </p>
        </section>

        <section class="rounded-xl border border-border bg-card p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Outstanding
                    </p>
                    <p class="font-mono text-2xl font-semibold">
                        {formatMoney(summary.outstanding.total)}
                    </p>
                </div>
                <Clock class="size-4 shrink-0 text-muted-foreground" />
            </div>
            {#if summary.outstanding.overdue_count > 0}
                <p class="mt-3 text-xs text-discount">
                    {summary.outstanding.overdue_count} overdue ·
                    {formatMoney(summary.outstanding.overdue_total)}
                </p>
            {:else}
                <p class="mt-3 text-xs text-muted-foreground">
                    {summary.outstanding.count} unpaid invoice{summary.outstanding
                        .count === 1
                        ? ''
                        : 's'}
                </p>
            {/if}
        </section>

        <section class="rounded-xl border border-border bg-card p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Invoices
                    </p>
                    <p class="font-mono text-2xl font-semibold">
                        {summary.issued}
                    </p>
                </div>
                <ReceiptText class="size-4 shrink-0 text-muted-foreground" />
            </div>
            <p class="mt-3 text-xs text-muted-foreground">
                {summary.billed.count} confirmed · avg
                {formatMoney(summary.billed.average)}
            </p>
        </section>

        <section class="rounded-xl border border-border bg-card p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Tax collected
                    </p>
                    <p class="font-mono text-2xl font-semibold">
                        {formatMoney(summary.billed.tax)}
                    </p>
                </div>
                <Landmark class="size-4 shrink-0 text-muted-foreground" />
            </div>
            <p class="mt-3 text-xs text-muted-foreground">
                Included in the billed total.
            </p>
        </section>
    </div>

    <section class="rounded-xl border border-border bg-card p-5">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
            <div class="space-y-0.5">
                <h2 class="text-sm font-semibold">Billed per day</h2>
                <p class="text-xs text-muted-foreground">{trendCaption}</p>
            </div>
            <p class="font-mono text-sm text-muted-foreground">
                {formatMoney(trendTotal)}
            </p>
        </div>

        <RevenueTrend points={trend} />

        {#if trendTotal === 0}
            <p class="mt-3 text-xs text-muted-foreground">
                No sent or paid invoices in this period.
            </p>
        {/if}
    </section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <section class="rounded-xl border border-border bg-card p-5">
            <div class="mb-4 space-y-0.5">
                <h2 class="text-sm font-semibold">Needs attention</h2>
                <p class="text-xs text-muted-foreground">
                    Overdue invoices and unfinished drafts.
                </p>
            </div>

            {#if overdueInvoices.length > 0}
                <ul class="divide-y divide-border text-sm">
                    {#each overdueInvoices as invoice (invoice.id)}
                        <li
                            class="flex items-start justify-between gap-3 py-2 first:pt-0"
                        >
                            <div class="min-w-0">
                                <Link
                                    href={show(invoice.id).url}
                                    class="font-mono text-xs font-medium hover:underline"
                                >
                                    {invoice.number}
                                </Link>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {invoice.customer ?? '—'} · {invoice.days_overdue}
                                    day{invoice.days_overdue === 1
                                        ? ''
                                        : 's'} late
                                </p>
                            </div>
                            <span
                                class="shrink-0 font-mono text-xs text-discount"
                            >
                                {formatMoney(invoice.total)}
                            </span>
                        </li>
                    {/each}
                </ul>
            {/if}

            {#if draftCount > 0}
                <div
                    class="mt-3 flex items-center justify-between gap-3 rounded-lg bg-surface-2 px-3 py-2 text-xs"
                >
                    <span>
                        {draftCount} draft{draftCount === 1 ? '' : 's'} waiting
                    </span>
                    <Link
                        href={`${index().url}?status=draft`}
                        class="font-medium text-link hover:underline"
                    >
                        Review
                    </Link>
                </div>
            {/if}

            {#if overdueInvoices.length === 0 && draftCount === 0}
                <p class="text-sm text-muted-foreground">
                    Nothing overdue and no drafts. All caught up.
                </p>
            {/if}
        </section>

        <section class="rounded-xl border border-border bg-card p-5">
            <div class="mb-4 space-y-0.5">
                <h2 class="text-sm font-semibold">Top products</h2>
                <p class="text-xs text-muted-foreground">
                    By billed value in this period.
                </p>
            </div>

            {#if topProducts.length > 0}
                <ol class="space-y-3 text-sm">
                    {#each topProducts as product (product.id)}
                        <li class="flex items-baseline justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium">
                                    {product.name}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {formatQuantity(product.quantity)} sold
                                </p>
                            </div>
                            <span class="shrink-0 font-mono text-xs">
                                {formatMoney(product.revenue)}
                            </span>
                        </li>
                    {/each}
                </ol>
            {:else}
                <p class="text-sm text-muted-foreground">
                    No product lines billed in this period.
                </p>
            {/if}
        </section>

        <section class="rounded-xl border border-border bg-card p-5">
            <div class="mb-4 space-y-0.5">
                <h2 class="text-sm font-semibold">Top customers</h2>
                <p class="text-xs text-muted-foreground">
                    By billed value in this period.
                </p>
            </div>

            {#if topCustomers.length > 0}
                <ol class="space-y-3 text-sm">
                    {#each topCustomers as customer (customer.id)}
                        <li class="flex items-baseline justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium">
                                    {customer.name}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {customer.invoice_count} invoice{customer.invoice_count ===
                                    1
                                        ? ''
                                        : 's'}
                                </p>
                            </div>
                            <span class="shrink-0 font-mono text-xs">
                                {formatMoney(customer.revenue)}
                            </span>
                        </li>
                    {/each}
                </ol>
            {:else}
                <p class="text-sm text-muted-foreground">
                    No invoices billed in this period.
                </p>
            {/if}
        </section>
    </div>

    <section class="rounded-xl border border-border bg-card">
        <div
            class="flex items-center justify-between gap-2 border-b border-border px-5 py-4"
        >
            <h2 class="text-sm font-semibold">Recent invoices</h2>
            <Link
                href={index().url}
                class="text-xs font-medium text-link hover:underline"
            >
                View all
            </Link>
        </div>

        {#if recentInvoices.length > 0}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="bg-table-head text-left text-xs tracking-wide text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-5 py-3 font-medium">Number</th>
                            <th class="px-5 py-3 font-medium">Customer</th>
                            <th class="px-5 py-3 font-medium">Issued</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        {#each recentInvoices as invoice (invoice.id)}
                            <tr class="hover:bg-row-hover">
                                <td class="px-5 py-3 font-mono font-medium">
                                    <Link
                                        href={show(invoice.id).url}
                                        class="hover:underline"
                                    >
                                        {invoice.number}
                                    </Link>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">
                                    {invoice.customer ?? '—'}
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">
                                    {formatDate(invoice.issue_date)}
                                </td>
                                <td class="px-5 py-3">
                                    <InvoiceStatusBadge
                                        status={invoice.status}
                                        label={statusLabel(invoice.status)}
                                    />
                                </td>
                                <td class="px-5 py-3 text-right font-mono">
                                    {formatMoney(invoice.total)}
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        {:else}
            <div class="px-5 py-10 text-center">
                <p class="text-sm text-muted-foreground">No invoices yet.</p>
                <Button variant="secondary" size="sm" class="mt-4" asChild>
                    {#snippet children(props)}
                        <Link {...props} href={create().url} class={props.class}>
                            <Plus class="size-4" />
                            Create your first invoice
                        </Link>
                    {/snippet}
                </Button>
            </div>
        {/if}
    </section>
</AppPage>
