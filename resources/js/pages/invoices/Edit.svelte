<script module lang="ts">
    import { index as invoicesIndex } from '@/routes/invoices';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Invoices',
                href: invoicesIndex(),
            },
            {
                title: 'Edit invoice',
                href: invoicesIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import InvoiceForm from '@/components/forms/InvoiceForm.svelte';
    import { index, update } from '@/routes/invoices';
    import type {
        CustomerOption,
        Invoice,
        InvoiceLine,
        InvoiceProductOption,
    } from '@/types';

    let {
        invoice,
        items,
        customers,
        products,
    }: {
        invoice: Invoice;
        items: InvoiceLine[];
        customers: CustomerOption[];
        products: InvoiceProductOption[];
    } = $props();
</script>

<AppHead title={`Edit ${invoice.number}`} />

<AppPage
    title={invoice.number}
    description="Update the invoice details and line items."
>
    <InvoiceForm
        action={update(invoice.id).url}
        method="put"
        {invoice}
        {items}
        {customers}
        {products}
        cancelHref={index().url}
        submitLabel="Save changes"
    />
</AppPage>
