<script module lang="ts">
    import { index as categoriesIndex } from '@/routes/categories';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Categories',
                href: categoriesIndex(),
            },
            {
                title: 'Edit category',
                href: categoriesIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import CategoryForm from '@/components/forms/CategoryForm.svelte';
    import { index, update } from '@/routes/categories';
    import type { CategoryOption, ProductCategory } from '@/types';

    let {
        category,
        categories = [],
    }: {
        category: ProductCategory;
        categories?: CategoryOption[];
    } = $props();
</script>

<AppHead title={`Edit ${category.name}`} />

<AppPage title={category.name} description="Update this category's details.">
    <div class="max-w-2xl">
        <CategoryForm
            action={update(category.id).url}
            method="put"
            {category}
            {categories}
            cancelHref={index().url}
            submitLabel="Save changes"
        />
    </div>
</AppPage>
