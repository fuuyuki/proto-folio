<script module lang="ts">
    import { index as taxRatesIndex } from '@/routes/tax-rates';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Tax rates',
                href: taxRatesIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Percent from '@lucide/svelte/icons/percent';
    import Plus from '@lucide/svelte/icons/plus';
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { create, destroy, edit } from '@/routes/tax-rates';
    import type { Paginated, TaxRate } from '@/types';

    let { taxRates }: { taxRates: Paginated<TaxRate> } = $props();
</script>

<AppHead title="Tax rates" />

<AppPage
    title="Tax rates"
    description="The tax rates products can use, inclusive or added on top."
>
    {#snippet actions()}
        <Button asChild>
            {#snippet children(props)}
                <Link {...props} href={create().url} class={props.class}>
                    <Plus class="size-4" />
                    New tax rate
                </Link>
            {/snippet}
        </Button>
    {/snippet}

    {#if taxRates.data.length === 0}
        <EmptyState
            icon={Percent}
            title="No tax rates yet"
            description="Add a rate like PPN 11% or a zero-rated tax."
        />
    {:else}
        <div class="overflow-x-auto rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead
                    class="border-b border-border bg-muted/50 text-left text-xs tracking-wide text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 text-right font-medium">Rate</th>
                        <th class="px-4 py-3 font-medium">Pricing</th>
                        <th class="px-4 py-3 text-right font-medium">Products</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    {#each taxRates.data as taxRate (taxRate.id)}
                        <tr class="hover:bg-muted/40">
                            <td class="px-4 py-3 font-medium">{taxRate.name}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {Number.parseFloat(taxRate.rate)}%
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {taxRate.is_inclusive
                                    ? 'Inclusive'
                                    : 'Added on top'}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {taxRate.products_count ?? 0}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    variant={taxRate.is_active
                                        ? 'secondary'
                                        : 'outline'}
                                >
                                    {taxRate.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="sm" asChild>
                                        {#snippet children(props)}
                                            <Link
                                                {...props}
                                                href={edit(taxRate.id).url}
                                                class={props.class}
                                            >
                                                Edit
                                            </Link>
                                        {/snippet}
                                    </Button>

                                    <ConfirmDeleteButton
                                        action={destroy(taxRate.id).url}
                                        title={`Delete ${taxRate.name}?`}
                                        description="Products using this rate keep their details but lose the tax rate."
                                    />
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <Pagination paginator={taxRates} itemLabel="tax rates" />
    {/if}
</AppPage>
