<script module lang="ts">
    import { create, index as categoriesIndex } from '@/routes/categories';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Categories',
                href: categoriesIndex(),
            },
            {
                title: 'New category',
                href: create(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import CategoryForm from '@/components/forms/CategoryForm.svelte';
    import { index, store } from '@/routes/categories';
    import type { CategoryOption } from '@/types';

    let { categories = [] }: { categories?: CategoryOption[] } = $props();
</script>

<AppHead title="New category" />

<AppPage
    title="New category"
    description="Group related products under a label."
>
    <div class="max-w-2xl">
        <CategoryForm
            action={store().url}
            method="post"
            cancelHref={index().url}
            submitLabel="Create category"
            {categories}
        />
    </div>
</AppPage>
