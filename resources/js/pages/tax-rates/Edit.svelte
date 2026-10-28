<script module lang="ts">
    import { index as taxRatesIndex } from '@/routes/tax-rates';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Tax rates',
                href: taxRatesIndex(),
            },
            {
                title: 'Edit tax rate',
                href: taxRatesIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import TaxRateForm from '@/components/forms/TaxRateForm.svelte';
    import { index, update } from '@/routes/tax-rates';
    import type { TaxRate } from '@/types';

    let { taxRate }: { taxRate: TaxRate } = $props();
</script>

<AppHead title={`Edit ${taxRate.name}`} />

<AppPage title={taxRate.name} description="Update this tax rate.">
    <div class="max-w-2xl">
        <TaxRateForm
            action={update(taxRate.id).url}
            method="put"
            {taxRate}
            cancelHref={index().url}
            submitLabel="Save changes"
        />
    </div>
</AppPage>
