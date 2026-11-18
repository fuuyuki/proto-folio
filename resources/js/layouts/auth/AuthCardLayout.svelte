<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
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
    class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10"
>
    <div class="flex w-full max-w-md flex-col gap-6">
        <Link
            href={home()}
            class="flex items-center gap-2 self-center font-medium"
        >
            <img
                src="/protofolio-text.svg"
                alt={name}
                class="h-12 w-auto dark:hidden"
            />
            <img
                src="/protofolio-text-white.svg"
                alt={name}
                class="hidden h-12 w-auto dark:block"
            />
        </Link>

        <div class="flex flex-col gap-6">
            <Card class="rounded-xl">
                <CardHeader class="px-10 pt-8 pb-0 text-center">
                    <CardTitle class="text-xl">{title}</CardTitle>
                    <CardDescription>
                        {description}
                    </CardDescription>
                </CardHeader>
                <CardContent class="px-10 py-8">
                    {@render children?.()}
                </CardContent>
            </Card>
        </div>
    </div>
</div>
