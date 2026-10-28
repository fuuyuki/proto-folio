<script module lang="ts">
    import { index as categoriesIndex } from '@/routes/categories';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Categories',
                href: categoriesIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Tags from '@lucide/svelte/icons/tags';
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { create, destroy, edit, index } from '@/routes/categories';
    import type { Paginated, ProductCategory } from '@/types';

    let {
        categories,
        filters,
    }: {
        categories: Paginated<ProductCategory>;
        filters: { search: string };
    } = $props();
</script>

<AppHead title="Categories" />

<AppPage
    title="Categories"
    description="Group products so the POS grid and reports stay organised."
>
    {#snippet actions()}
        <Button asChild>
            {#snippet children(props)}
                <Link {...props} href={create().url} class={props.class}>
                    <Plus class="size-4" />
                    New category
                </Link>
            {/snippet}
        </Button>
    {/snippet}

    <SearchField
        url={index().url}
        value={filters.search}
        placeholder="Search categories…"
    />

    {#if categories.data.length === 0}
        <EmptyState
            icon={Tags}
            title={filters.search ? 'No categories found' : 'No categories yet'}
            description={filters.search
                ? 'Try a different name.'
                : 'Create categories like Drinks, Food, or Services.'}
        />
    {:else}
        <div class="overflow-x-auto rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead
                    class="border-b border-border bg-muted/50 text-left text-xs tracking-wide text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3 font-medium">Category</th>
                        <th class="px-4 py-3 font-medium">Parent</th>
                        <th class="px-4 py-3 text-right font-medium">Products</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    {#each categories.data as category (category.id)}
                        <tr class="hover:bg-muted/40">
                            <td class="px-4 py-3">
                                <div class="font-medium">{category.name}</div>
                                <div class="text-xs text-muted-foreground">
                                    {category.slug}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {category.parent?.name ?? '—'}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {category.products_count ?? 0}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    variant={category.is_active
                                        ? 'secondary'
                                        : 'outline'}
                                >
                                    {category.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="sm" asChild>
                                        {#snippet children(props)}
                                            <Link
                                                {...props}
                                                href={edit(category.id).url}
                                                class={props.class}
                                            >
                                                Edit
                                            </Link>
                                        {/snippet}
                                    </Button>

                                    <ConfirmDeleteButton
                                        action={destroy(category.id).url}
                                        title={`Delete ${category.name}?`}
                                        description="Products in this category keep their details but lose the category."
                                    />
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <Pagination paginator={categories} itemLabel="categories" />
    {/if}
</AppPage>
