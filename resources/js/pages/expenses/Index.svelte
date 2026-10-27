<script module lang="ts">
    import { index } from '@/routes/expenses';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Expenses',
                href: index(),
            },
        ],
    };
</script>

<script lang="ts">
    import Wallet from '@lucide/svelte/icons/wallet';
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
</script>

<AppHead title="Expenses" />

<AppPage
    title="Expenses"
    description="Log business spending so profit on the dashboard stays accurate."
>
    <EmptyState
        icon={Wallet}
        title="No expenses logged"
        description="Record purchases, subscriptions, and other business costs here."
    />
</AppPage>
