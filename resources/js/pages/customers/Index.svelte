<script module lang="ts">
    import { index as customersIndex } from '@/routes/customers';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Customers',
                href: customersIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Users from '@lucide/svelte/icons/users';
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import { Button } from '@/components/ui/button';
    import { create, destroy, edit, index } from '@/routes/customers';
    import type { Customer, Paginated } from '@/types';

    let {
        customers,
        filters,
    }: {
        customers: Paginated<Customer>;
        filters: { search: string };
    } = $props();
</script>

<AppHead title="Customers" />

<AppPage
    title="Customers"
    description="Keep your clients' contact and billing details in one place."
>
    {#snippet actions()}
        <Button asChild>
            {#snippet children(props)}
                <Link {...props} href={create().url} class={props.class}>
                    <Plus class="size-4" />
                    New customer
                </Link>
            {/snippet}
        </Button>
    {/snippet}

    <div class="flex flex-wrap items-center justify-between gap-3">
        <SearchField
            url={index().url}
            value={filters.search}
            placeholder="Search customers…"
        />
    </div>

    {#if customers.data.length === 0}
        <EmptyState
            icon={Users}
            title={filters.search
                ? 'No customers found'
                : 'No customers yet'}
            description={filters.search
                ? 'Try a different name, email, or phone number.'
                : 'Add your first customer to start invoicing.'}
        />
    {:else}
        <div class="overflow-x-auto rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead
                    class="border-b border-border bg-muted/50 text-left text-xs tracking-wide text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3 font-medium">Customer</th>
                        <th class="px-4 py-3 font-medium">Phone</th>
                        <th class="px-4 py-3 font-medium">Invoices</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    {#each customers.data as customer (customer.id)}
                        <tr class="hover:bg-muted/40">
                            <td class="px-4 py-3">
                                <div class="font-medium">{customer.name}</div>
                                {#if customer.email}
                                    <div class="text-xs text-muted-foreground">
                                        {customer.email}
                                    </div>
                                {/if}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {customer.phone ?? '—'}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {customer.invoices_count ?? 0}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="sm" asChild>
                                        {#snippet children(props)}
                                            <Link
                                                {...props}
                                                href={edit(customer.id).url}
                                                class={props.class}
                                            >
                                                Edit
                                            </Link>
                                        {/snippet}
                                    </Button>

                                    <ConfirmDeleteButton
                                        action={destroy(customer.id).url}
                                        title={`Delete ${customer.name}?`}
                                        description="The customer and their contact details will be removed permanently."
                                        disabled={(customer.invoices_count ?? 0) > 0}
                                        disabledReason="Customers with invoices cannot be deleted."
                                    />
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <Pagination paginator={customers} itemLabel="customers" />
    {/if}
</AppPage>
