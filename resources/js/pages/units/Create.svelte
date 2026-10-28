<script module lang="ts">
    import { create, index as unitsIndex } from '@/routes/units';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Units',
                href: unitsIndex(),
            },
            {
                title: 'New unit',
                href: create(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import UnitForm from '@/components/forms/UnitForm.svelte';
    import { index, store } from '@/routes/units';
    import type { UnitOption } from '@/types';

    let { units = [] }: { units?: UnitOption[] } = $props();
</script>

<AppHead title="New unit" />

<AppPage title="New unit" description="Define how you measure what you sell.">
    <div class="max-w-2xl">
        <UnitForm
            action={store().url}
            method="post"
            cancelHref={index().url}
            submitLabel="Create unit"
            {units}
        />
    </div>
</AppPage>
