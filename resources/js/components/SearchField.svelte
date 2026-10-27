<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Search from '@lucide/svelte/icons/search';
    import { untrack } from 'svelte';
    import { Input } from '@/components/ui/input';

    let {
        url,
        value = '',
        placeholder = 'Search…',
    }: {
        url: string;
        value?: string;
        placeholder?: string;
    } = $props();

    let term = $state(untrack(() => value));
    let timer: ReturnType<typeof setTimeout> | undefined;

    function search(): void {
        clearTimeout(timer);

        timer = setTimeout(() => {
            router.get(
                url,
                term === '' ? {} : { search: term },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 300);
    }
</script>

<div class="relative w-full max-w-sm">
    <Search
        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
    />
    <Input
        bind:value={term}
        oninput={search}
        {placeholder}
        aria-label={placeholder}
        class="pl-9"
    />
</div>
