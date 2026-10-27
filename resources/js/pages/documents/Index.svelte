<script module lang="ts">
    import { index } from '@/routes/documents';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Documents',
                href: index(),
            },
        ],
    };
</script>

<script lang="ts">
    import FileText from '@lucide/svelte/icons/file-text';
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
</script>

<AppHead title="Documents" />

<AppPage
    title="Documents"
    description="Store receipts, contracts, and other files attached to your books."
>
    <EmptyState
        icon={FileText}
        title="No documents yet"
        description="Uploaded files will be listed here."
    />
</AppPage>
