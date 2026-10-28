<script module lang="ts">
    import { index as unitsIndex } from '@/routes/units';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Units',
                href: unitsIndex(),
            },
            {
                title: 'Edit unit',
                href: unitsIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppPage from '@/components/AppPage.svelte';
    import UnitForm from '@/components/forms/UnitForm.svelte';
    import { index, update } from '@/routes/units';
    import type { Unit, UnitOption } from '@/types';

    let {
        unit,
        units = [],
    }: {
        unit: Unit;
        units?: UnitOption[];
    } = $props();
</script>

<AppHead title={`Edit ${unit.name}`} />

<AppPage title={unit.name} description="Update this unit's details.">
    <div class="max-w-2xl">
        <UnitForm
            action={update(unit.id).url}
            method="put"
            {unit}
            {units}
            cancelHref={index().url}
            submitLabel="Save changes"
        />
    </div>
</AppPage>
