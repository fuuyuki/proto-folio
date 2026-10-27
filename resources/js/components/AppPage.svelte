<script lang="ts">
    import type { Snippet } from 'svelte';

    let {
        title,
        description = '',
        actions,
        children,
    }: {
        title: string;
        description?: string;
        actions?: Snippet;
        children?: Snippet;
    } = $props();
</script>

<div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold tracking-tight">{title}</h1>
            {#if description}
                <p class="text-sm text-muted-foreground">{description}</p>
            {/if}
        </div>

        {#if actions}
            <div class="flex items-center gap-2">
                {@render actions()}
            </div>
        {/if}
    </div>

    {@render children?.()}
</div>
