<script lang="ts">
    import { page } from '@inertiajs/svelte';

    let { variant = 'auto' }: { variant?: 'auto' | 'sidebar' } = $props();

    const name = $derived(page.props.name);
</script>

{#if variant === 'sidebar'}
    <!-- The sidebar rail is dark in both themes. -->
    <img
        src="/protofolio-text-white.svg"
        alt={name}
        class="h-10 w-auto group-data-[collapsible=icon]:hidden"
    />
    <img
        src="/favicon.svg"
        alt={name}
        class="hidden size-8 rounded-md group-data-[collapsible=icon]:block"
    />
{:else}
    <img
        src="/protofolio-text.svg"
        alt={name}
        class="h-8 w-auto dark:hidden"
    />
    <img
        src="/protofolio-text-white.svg"
        alt={name}
        class="hidden h-8 w-auto dark:block"
    />
{/if}
