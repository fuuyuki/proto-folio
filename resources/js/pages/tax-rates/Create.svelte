<script module lang="ts">
    import { create, index as taxRatesIndex } from '@/routes/tax-rates';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Tax rates',
                href: taxRatesIndex(),
            },
            {
                title: 'New tax rate',
                href: create(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import TaxRateForm from '@/components/forms/TaxRateForm.svelte';
    import { index, store } from '@/routes/tax-rates';
</script>

<AppHead title="New tax rate" />

<AppPage title="New tax rate" description="Define a tax that products can use.">
    <div class="max-w-2xl">
        <TaxRateForm
            action={store().url}
            method="post"
            cancelHref={index().url}
            submitLabel="Create tax rate"
        />
    </div>
</AppPage>
