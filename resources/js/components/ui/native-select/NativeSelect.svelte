<script lang="ts">
    import { tick } from 'svelte';
    import type { Snippet } from 'svelte';
    import type { HTMLSelectAttributes } from 'svelte/elements';
    import { cn } from '@/lib/utils';

    let {
        class: className = '',
        value = $bindable(''),
        children,
        ...rest
    }: HTMLSelectAttributes & { children?: Snippet } = $props();

    let select = $state<HTMLSelectElement | null>(null);

    /*
     * Dynamic option lists (categories, units, products, customers) are
     * rendered after the select is mounted, so the initial value can miss its
     * option and the select falls back to blank. Re-apply the value after the
     * DOM has flushed, including when options change later.
     */
    $effect(() => {
        const current = value;

        void tick().then(() => {
            if (select && select.value !== current) {
                select.value = current;
            }
        });
    });
</script>

<select
    bind:this={select}
    bind:value
    class={cn(
        'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        className,
    )}
    {...rest}
>
    {@render children?.()}
</select>
