<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import { home } from '@/routes';

    let {
        title = '',
        description = '',
        children,
    }: {
        title?: string;
        description?: string;
        children?: Snippet;
    } = $props();

    const name = $derived(page.props.name);
</script>

<div
    class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
>
    <div class="w-full max-w-sm">
        <div class="flex flex-col gap-8">
            <div class="flex flex-col items-center gap-4">
                <Link
                    href={home()}
                    class="flex flex-col items-center gap-2 font-medium"
                >
                    <img
                        src="/protofolio-text.svg"
                        alt={name}
                        class="h-14 w-auto dark:hidden"
                    />
                    <img
                        src="/protofolio-text-white.svg"
                        alt={name}
                        class="hidden h-14 w-auto dark:block"
                    />
                </Link>
                <div class="space-y-2 text-center">
                    <h1 class="text-xl font-medium">{title}</h1>
                    <p class="text-center text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>
            </div>
            {@render children?.()}
        </div>
    </div>
</div>
