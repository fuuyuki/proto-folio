<script module lang="ts">
    import { create, index as customersIndex } from '@/routes/customers';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Customers',
                href: customersIndex(),
            },
            {
                title: 'New customer',
                href: create(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import CustomerForm from '@/components/forms/CustomerForm.svelte';
    import { index, store } from '@/routes/customers';
</script>

<AppHead title="New customer" />

<AppPage
    title="New customer"
    description="Add a client so you can bill them."
>
    <div class="max-w-2xl">
        <CustomerForm
            action={store().url}
            method="post"
            cancelHref={index().url}
            submitLabel="Create customer"
        />
    </div>
</AppPage>
