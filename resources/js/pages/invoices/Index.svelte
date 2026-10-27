<script module lang="ts">
    import { index } from '@/routes/invoices';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Invoices',
                href: index(),
            },
        ],
    };
</script>

<script lang="ts">
    import ReceiptText from '@lucide/svelte/icons/receipt-text';
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
</script>

<AppHead title="Invoices" />

<AppPage
    title="Invoices"
    description="Create invoices, track what customers owe, and print 80mm receipts."
>
    <EmptyState
        icon={ReceiptText}
        title="No invoices yet"
        description="Invoices you create will show up here with their status and total."
    />
</AppPage>
