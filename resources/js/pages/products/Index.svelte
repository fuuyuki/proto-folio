<script module lang="ts">
    import { index as productsIndex } from '@/routes/products';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Products',
                href: productsIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Package from '@lucide/svelte/icons/package';
    import Plus from '@lucide/svelte/icons/plus';
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import { Button } from '@/components/ui/button';
    import { Badge } from '@/components/ui/badge';
    import { formatMoney } from '@/lib/currency';
    import { create, destroy, edit, index } from '@/routes/products';
    import type { Paginated, Product } from '@/types';

    let {
        products,
        filters,
    }: {
        products: Paginated<Product>;
        filters: { search: string };
    } = $props();
</script>

<AppHead title="Products" />

<AppPage
    title="Products"
    description="Manage the products and services you bill for, with their price and SKU."
>
    {#snippet actions()}
        <Button asChild>
            {#snippet children(props)}
                <Link {...props} href={create().url} class={props.class}>
                    <Plus class="size-4" />
                    New product
                </Link>
            {/snippet}
        </Button>
    {/snippet}

    <div class="flex flex-wrap items-center justify-between gap-3">
        <SearchField
            url={index().url}
            value={filters.search}
            placeholder="Search products…"
        />
    </div>

    {#if products.data.length === 0}
        <EmptyState
            icon={Package}
            title={filters.search ? 'No products found' : 'No products yet'}
            description={filters.search
                ? 'Try a different name or SKU.'
                : 'Add products and services to pick them when you write an invoice.'}
        />
    {:else}
        <div class="overflow-x-auto rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead
                    class="border-b border-border bg-muted/50 text-left text-xs tracking-wide text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3 font-medium">Product</th>
                        <th class="px-4 py-3 font-medium">SKU</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Category</th>
                        <th class="px-4 py-3 text-right font-medium">Price</th>
                        <th class="px-4 py-3 font-medium">Used in</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    {#each products.data as product (product.id)}
                        <tr class="hover:bg-muted/40">
                            <td class="px-4 py-3">
                                <div class="font-medium">{product.name}</div>
                                {#if product.description}
                                    <div
                                        class="line-clamp-1 text-xs text-muted-foreground"
                                    >
                                        {product.description}
                                    </div>
                                {/if}
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {product.sku}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    variant={product.type === 'service'
                                        ? 'outline'
                                        : 'secondary'}
                                >
                                    {product.type === 'service'
                                        ? 'Service'
                                        : 'Product'}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {product.category?.name ?? '—'}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {formatMoney(product.price)}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {product.invoice_items_count ?? 0}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="sm" asChild>
                                        {#snippet children(props)}
                                            <Link
                                                {...props}
                                                href={edit(product.id).url}
                                                class={props.class}
                                            >
                                                Edit
                                            </Link>
                                        {/snippet}
                                    </Button>

                                    <ConfirmDeleteButton
                                        action={destroy(product.id).url}
                                        title={`Delete ${product.name}?`}
                                        description="The product will be removed. Existing invoice lines are kept but will no longer reference it."
                                    />
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <Pagination paginator={products} itemLabel="products" />
    {/if}
</AppPage>
