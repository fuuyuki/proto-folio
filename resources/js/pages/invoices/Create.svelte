<script module lang="ts">
    import { create, index as invoicesIndex } from '@/routes/invoices';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Invoices',
                href: invoicesIndex(),
            },
            {
                title: 'New invoice',
                href: create(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import InvoiceForm from '@/components/forms/InvoiceForm.svelte';
    import { index, store } from '@/routes/invoices';
    import type { CustomerOption, InvoiceProductOption } from '@/types';

    let {
        customers,
        products,
    }: {
        customers: CustomerOption[];
        products: InvoiceProductOption[];
    } = $props();
</script>

<AppHead title="New invoice" />

<AppPage
    title="New invoice"
    description="Bill a customer for products or services."
>
    <InvoiceForm
        action={store().url}
        method="post"
        {customers}
        {products}
        cancelHref={index().url}
        submitLabel="Create invoice"
    />
</AppPage>
