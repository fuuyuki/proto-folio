<script module lang="ts">
    import { index as productsIndex } from '@/routes/products';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Products',
                href: productsIndex(),
            },
            {
                title: 'Edit product',
                href: productsIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import ProductForm from '@/components/forms/ProductForm.svelte';
    import { index, update } from '@/routes/products';
    import type {
        CategoryOption,
        Product,
        TaxRateOption,
        UnitOption,
    } from '@/types';

    let {
        product,
        categories = [],
        units = [],
        taxRates = [],
    }: {
        product: Product;
        categories?: CategoryOption[];
        units?: UnitOption[];
        taxRates?: TaxRateOption[];
    } = $props();
</script>

<AppHead title={`Edit ${product.name}`} />

<AppPage title={product.name} description="Update this product's details.">
    <div class="max-w-3xl">
        <ProductForm
            action={update(product.id).url}
            method="put"
            {product}
            cancelHref={index().url}
            submitLabel="Save changes"
            {categories}
            {units}
            {taxRates}
        />
    </div>
</AppPage>
