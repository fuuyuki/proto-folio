<script lang="ts">
    import type { Component, Snippet } from 'svelte';

    let {
        icon,
        title,
        description = '',
        children,
    }: {
        icon?: Component<{ class?: string }>;
        title: string;
        description?: string;
        children?: Snippet;
    } = $props();

    const Icon = $derived(icon);
</script>

<div
    class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-border p-10 text-center"
>
    {#if Icon}
        <div
            class="mb-1 flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground"
        >
            <Icon class="size-5" />
        </div>
    {/if}

    <p class="text-sm font-medium">{title}</p>

    {#if description}
        <p class="max-w-sm text-sm text-muted-foreground">{description}</p>
    {/if}

    {@render children?.()}
</div>
