<script module lang="ts">
    import { index as unitsIndex } from '@/routes/units';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Units',
                href: unitsIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Ruler from '@lucide/svelte/icons/ruler';
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { create, destroy, edit, index } from '@/routes/units';
    import type { Paginated, Unit } from '@/types';

    let {
        units,
        filters,
    }: {
        units: Paginated<Unit>;
        filters: { search: string };
    } = $props();
</script>

<AppHead title="Units" />

<AppPage
    title="Units"
    description="Sell by piece, box, kilogram, or hour — with conversions between them."
>
    {#snippet actions()}
        <Button asChild>
            {#snippet children(props)}
                <Link {...props} href={create().url} class={props.class}>
                    <Plus class="size-4" />
                    New unit
                </Link>
            {/snippet}
        </Button>
    {/snippet}

    <SearchField
        url={index().url}
        value={filters.search}
        placeholder="Search units…"
    />

    {#if units.data.length === 0}
        <EmptyState
            icon={Ruler}
            title={filters.search ? 'No units found' : 'No units yet'}
            description={filters.search
                ? 'Try a different name or code.'
                : 'Add units like pcs, box, kg, or hour.'}
        />
    {:else}
        <div class="overflow-x-auto rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead
                    class="border-b border-border bg-muted/50 text-left text-xs tracking-wide text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3 font-medium">Unit</th>
                        <th class="px-4 py-3 font-medium">Code</th>
                        <th class="px-4 py-3 font-medium">Conversion</th>
                        <th class="px-4 py-3 text-right font-medium">Products</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    {#each units.data as unit (unit.id)}
                        <tr class="hover:bg-muted/40">
                            <td class="px-4 py-3 font-medium">{unit.name}</td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {unit.code}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {#if unit.base_unit}
                                    1 {unit.code} =
                                    {Number.parseFloat(unit.factor)}
                                    {unit.base_unit.code}
                                {:else}
                                    Base unit
                                {/if}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {unit.products_count ?? 0}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    variant={unit.is_active
                                        ? 'secondary'
                                        : 'outline'}
                                >
                                    {unit.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="sm" asChild>
                                        {#snippet children(props)}
                                            <Link
                                                {...props}
                                                href={edit(unit.id).url}
                                                class={props.class}
                                            >
                                                Edit
                                            </Link>
                                        {/snippet}
                                    </Button>

                                    <ConfirmDeleteButton
                                        action={destroy(unit.id).url}
                                        title={`Delete ${unit.name}?`}
                                        description="Products using this unit keep their details but lose the unit."
                                    />
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <Pagination paginator={units} itemLabel="units" />
    {/if}
</AppPage>
