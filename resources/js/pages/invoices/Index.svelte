<script module lang="ts">
    import { index as invoicesIndex } from '@/routes/invoices';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Invoices',
                href: invoicesIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import ReceiptText from '@lucide/svelte/icons/receipt-text';
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { NativeSelect } from '@/components/ui/native-select';
    import { formatMoney } from '@/lib/currency';
    import { create, destroy, edit, index, show } from '@/routes/invoices';
    import type { Invoice, InvoiceStatus, Paginated } from '@/types';

    let {
        invoices,
        filters,
        statuses,
    }: {
        invoices: Paginated<Invoice>;
        filters: { search: string; status: string };
        statuses: { value: InvoiceStatus; label: string }[];
    } = $props();

    const badgeVariant: Record<
        InvoiceStatus,
        'default' | 'secondary' | 'outline'
    > = {
        draft: 'secondary',
        sent: 'outline',
        paid: 'default',
    };

    function filterByStatus(event: Event): void {
        const value = (event.currentTarget as HTMLSelectElement).value;

        router.get(
            index().url,
            value === '' ? {} : { status: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }
</script>

<AppHead title="Invoices" />

<AppPage
    title="Invoices"
    description="Create invoices, track what customers owe, and print 58mm receipts."
>
    {#snippet actions()}
        <Button asChild>
            {#snippet children(props)}
                <Link {...props} href={create().url} class={props.class}>
                    <Plus class="size-4" />
                    New invoice
                </Link>
            {/snippet}
        </Button>
    {/snippet}

    <div class="flex flex-wrap items-center justify-between gap-3">
        <SearchField
            url={index().url}
            value={filters.search}
            placeholder="Search invoices…"
        />

        <NativeSelect
            class="w-40"
            value={filters.status}
            aria-label="Filter by status"
            onchange={filterByStatus}
        >
            <option value="">All statuses</option>
            {#each statuses as status (status.value)}
                <option value={status.value}>{status.label}</option>
            {/each}
        </NativeSelect>
    </div>

    {#if invoices.data.length === 0}
        <EmptyState
            icon={ReceiptText}
            title={filters.search || filters.status
                ? 'No invoices found'
                : 'No invoices yet'}
            description={filters.search || filters.status
                ? 'Try a different search term or status.'
                : 'Create your first invoice and print it to a thermal receipt.'}
        />
    {:else}
        <div class="overflow-x-auto rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead
                    class="border-b border-border bg-muted/50 text-left text-xs tracking-wide text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3 font-medium">Number</th>
                        <th class="px-4 py-3 font-medium">Customer</th>
                        <th class="px-4 py-3 font-medium">Issued</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    {#each invoices.data as invoice (invoice.id)}
                        <tr class="hover:bg-muted/40">
                            <td class="px-4 py-3 font-medium">
                                <Link
                                    href={show(invoice.id).url}
                                    class="hover:underline"
                                >
                                    {invoice.number}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {invoice.customer?.name ?? '—'}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {invoice.issue_date.slice(0, 10)}
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant={badgeVariant[invoice.status]}>
                                    {invoice.status}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {formatMoney(invoice.total_amount)}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="sm" asChild>
                                        {#snippet children(props)}
                                            <Link
                                                {...props}
                                                href={edit(invoice.id).url}
                                                class={props.class}
                                            >
                                                Edit
                                            </Link>
                                        {/snippet}
                                    </Button>

                                    <ConfirmDeleteButton
                                        action={destroy(invoice.id).url}
                                        title={`Delete ${invoice.number}?`}
                                        description="The invoice and its line items will be removed permanently."
                                    />
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <Pagination paginator={invoices} itemLabel="invoices" />
    {/if}
</AppPage>
