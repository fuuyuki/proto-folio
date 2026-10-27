<script module lang="ts">
    import { index as customersIndex } from '@/routes/customers';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Customers',
                href: customersIndex(),
            },
            {
                title: 'Edit customer',
                href: customersIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import CustomerForm from '@/components/forms/CustomerForm.svelte';
    import { index, update } from '@/routes/customers';
    import type { Customer } from '@/types';

    let { customer }: { customer: Customer } = $props();
</script>

<AppHead title={`Edit ${customer.name}`} />

<AppPage title={customer.name} description="Update this customer's details.">
    <div class="max-w-2xl">
        <CustomerForm
            action={update(customer.id).url}
            method="put"
            {customer}
            cancelHref={index().url}
            submitLabel="Save changes"
        />
    </div>
</AppPage>
