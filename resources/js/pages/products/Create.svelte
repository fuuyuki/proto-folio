<script module lang="ts">
    import { create, index as productsIndex } from '@/routes/products';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Products',
                href: productsIndex(),
            },
            {
                title: 'New product',
                href: create(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import ProductForm from '@/components/forms/ProductForm.svelte';
    import { index, store } from '@/routes/products';
</script>

<AppHead title="New product" />

<AppPage
    title="New product"
    description="Add something you bill for, like a service or a physical item."
>
    <div class="max-w-2xl">
        <ProductForm
            action={store().url}
            method="post"
            cancelHref={index().url}
            submitLabel="Create product"
        />
    </div>
</AppPage>
