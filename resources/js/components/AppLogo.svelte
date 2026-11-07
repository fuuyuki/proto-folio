<script lang="ts">
    import { page } from '@inertiajs/svelte';

    const name = $derived(page.props.name);
</script>

<div
    class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary"
>
    <img src="/favicon.svg" alt="" class="size-5 invert dark:invert-0" />
</div>
<div class="ml-1 grid flex-1 text-left text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">{name}</span>
</div>
