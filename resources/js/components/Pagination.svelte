<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import type { Paginated } from '@/types';

    let {
        paginator,
        itemLabel = 'results',
    }: {
        paginator: Paginated<unknown>;
        itemLabel?: string;
    } = $props();
</script>

{#if paginator.total > 0}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted-foreground">
            Showing {paginator.from ?? 0}–{paginator.to ?? 0} of {paginator.total}
            {itemLabel}
        </p>

        {#if paginator.last_page > 1}
            <div class="flex items-center gap-2">
                {#if paginator.prev_page_url}
                    <Button variant="outline" size="sm" asChild>
                        {#snippet children(props)}
                            <Link
                                {...props}
                                href={paginator.prev_page_url ?? '#'}
                                class={props.class}
                                preserveScroll
                            >
                                Previous
                            </Link>
                        {/snippet}
                    </Button>
                {:else}
                    <Button variant="outline" size="sm" disabled>Previous</Button>
                {/if}

                <span class="text-sm text-muted-foreground">
                    Page {paginator.current_page} of {paginator.last_page}
                </span>

                {#if paginator.next_page_url}
                    <Button variant="outline" size="sm" asChild>
                        {#snippet children(props)}
                            <Link
                                {...props}
                                href={paginator.next_page_url ?? '#'}
                                class={props.class}
                                preserveScroll
                            >
                                Next
                            </Link>
                        {/snippet}
                    </Button>
                {:else}
                    <Button variant="outline" size="sm" disabled>Next</Button>
                {/if}
            </div>
        {/if}
    </div>
{/if}
