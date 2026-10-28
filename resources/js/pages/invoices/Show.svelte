<script module lang="ts">
    import { index as invoicesIndex } from '@/routes/invoices';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Invoices',
                href: invoicesIndex(),
            },
            {
                title: 'Invoice',
                href: invoicesIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Printer from '@lucide/svelte/icons/printer';
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { formatMoney } from '@/lib/currency';
    import {
        destroy,
        edit,
        index,
        print as printInvoice,
    } from '@/routes/invoices';
    import type {
        Invoice,
        InvoiceLineOutput,
        InvoiceStatus,
    } from '@/types';

    let {
        invoice,
        items,
    }: {
        invoice: Invoice;
        items: InvoiceLineOutput[];
    } = $props();

    const badgeVariant: Record<
        InvoiceStatus,
        'default' | 'secondary' | 'outline'
    > = {
        draft: 'secondary',
        sent: 'outline',
        paid: 'default',
    };

    function formatDate(value: string | null): string {
        if (!value) {
            return '—';
        }

        return new Date(value).toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    }

    function formatQuantity(value: string): string {
        return Number.parseFloat(value).toString();
    }
</script>

<AppHead title={invoice.number} />

<AppPage
    title={invoice.number}
    description={`Issued ${formatDate(invoice.issue_date)}`}
>
    {#snippet actions()}
        <Button variant="secondary" asChild>
            {#snippet children(props)}
                <a
                    {...props}
                    href={printInvoice(invoice.id).url}
                    class={props.class}
                    target="_blank"
                    rel="noopener"
                >
                    <Printer class="size-4" />
                    Print receipt
                </a>
            {/snippet}
        </Button>

        <Button asChild>
            {#snippet children(props)}
                <Link {...props} href={edit(invoice.id).url} class={props.class}>
                    Edit
                </Link>
            {/snippet}
        </Button>

        <ConfirmDeleteButton
            action={destroy(invoice.id).url}
            title={`Delete ${invoice.number}?`}
            description="The invoice and its line items will be removed permanently."
        />
    {/snippet}

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-xl border border-border bg-card p-6">
            <h2 class="text-sm font-semibold">Billed to</h2>
            <p class="mt-2 font-medium">{invoice.customer?.name ?? '—'}</p>
            {#if invoice.customer?.email}
                <p class="text-sm text-muted-foreground">
                    {invoice.customer.email}
                </p>
            {/if}
            {#if invoice.customer?.phone}
                <p class="text-sm text-muted-foreground">
                    {invoice.customer.phone}
                </p>
            {/if}
            {#if invoice.customer?.address}
                <p class="mt-2 text-sm whitespace-pre-line text-muted-foreground">
                    {invoice.customer.address}
                </p>
            {/if}
        </section>

        <section class="rounded-xl border border-border bg-card p-6">
            <h2 class="text-sm font-semibold">Details</h2>
            <dl class="mt-2 space-y-2 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-muted-foreground">Status</dt>
                    <dd>
                        <Badge variant={badgeVariant[invoice.status]}>
                            {invoice.status}
                        </Badge>
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-muted-foreground">Issued</dt>
                    <dd>{formatDate(invoice.issue_date)}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-muted-foreground">Due</dt>
                    <dd>{formatDate(invoice.due_date)}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-xl border border-border bg-card p-6">
            <h2 class="text-sm font-semibold">Total</h2>
            <p class="mt-2 text-2xl font-semibold tabular-nums">
                {formatMoney(invoice.total_amount)}
            </p>
            <p class="text-xs text-muted-foreground">
                Includes {formatMoney(invoice.tax_amount)} tax
            </p>
        </section>
    </div>

    <div class="overflow-x-auto rounded-xl border border-border bg-card">
        <table class="w-full text-sm">
            <thead
                class="border-b border-border bg-muted/50 text-left text-xs tracking-wide text-muted-foreground uppercase"
            >
                <tr>
                    <th class="px-4 py-3 font-medium">Item</th>
                    <th class="px-4 py-3 text-right font-medium">Qty</th>
                    <th class="px-4 py-3 text-right font-medium">Price</th>
                    <th class="px-4 py-3 text-right font-medium">Tax</th>
                    <th class="px-4 py-3 text-right font-medium">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                {#each items as item (item.id)}
                    <tr>
                        <td class="px-4 py-3">{item.description}</td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {formatQuantity(item.quantity)}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {formatMoney(item.unit_price)}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {formatQuantity(item.tax_rate)}%
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {formatMoney(item.line_total)}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>

    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        {#if invoice.notes}
            <section class="max-w-xl rounded-xl border border-border bg-card p-6">
                <h2 class="text-sm font-semibold">Notes</h2>
                <p class="mt-2 text-sm whitespace-pre-line text-muted-foreground">
                    {invoice.notes}
                </p>
            </section>
        {/if}

        <dl class="ml-auto w-full max-w-xs space-y-2 text-sm">
            <div class="flex items-center justify-between gap-6">
                <dt class="text-muted-foreground">Subtotal</dt>
                <dd class="tabular-nums">{formatMoney(invoice.subtotal)}</dd>
            </div>
            <div class="flex items-center justify-between gap-6">
                <dt class="text-muted-foreground">Tax</dt>
                <dd class="tabular-nums">{formatMoney(invoice.tax_amount)}</dd>
            </div>
            <div
                class="flex items-center justify-between gap-6 border-t border-border pt-2 font-semibold"
            >
                <dt>Total</dt>
                <dd class="tabular-nums">{formatMoney(invoice.total_amount)}</dd>
            </div>
        </dl>
    </div>

    <div>
        <Button variant="ghost" asChild>
            {#snippet children(props)}
                <Link {...props} href={index().url} class={props.class}>
                    Back to invoices
                </Link>
            {/snippet}
        </Button>
    </div>
</AppPage>
