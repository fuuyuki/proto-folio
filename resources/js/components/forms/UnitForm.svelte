<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { NativeSelect } from '@/components/ui/native-select';
    import type { Unit, UnitOption } from '@/types';

    let {
        action,
        method,
        unit,
        units = [],
        cancelHref,
        submitLabel,
    }: {
        action: string;
        method: 'post' | 'put';
        unit?: Unit;
        units?: UnitOption[];
        cancelHref: string;
        submitLabel: string;
    } = $props();

    const baseUnitOptions = $derived(
        units.filter((option) => option.id !== unit?.id),
    );
    const factor = $derived(
        unit === undefined ? '1' : String(Number.parseFloat(unit.factor)),
    );
</script>

<Form {action} {method} options={{ preserveScroll: true }}>
    {#snippet children({ errors, processing })}
        <div
            class="grid gap-5 rounded-xl border border-border bg-card p-6 md:grid-cols-2"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" value={unit?.name ?? ''} required />
                <InputError message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="code">Code</Label>
                <Input
                    id="code"
                    name="code"
                    value={unit?.code ?? ''}
                    placeholder="pcs"
                    required
                />
                <InputError message={errors.code} />
            </div>

            <div class="grid gap-2">
                <Label for="base_unit_id">Base unit</Label>
                <NativeSelect
                    id="base_unit_id"
                    name="base_unit_id"
                    value={unit?.base_unit_id?.toString() ?? ''}
                >
                    <option value="">This is a base unit</option>
                    {#each baseUnitOptions as option (option.id)}
                        <option value={option.id}>
                            {option.name} ({option.code})
                        </option>
                    {/each}
                </NativeSelect>
                <p class="text-xs text-muted-foreground">
                    Pick a base unit when this unit is a multiple of another,
                    e.g. a box of 12 pieces.
                </p>
                <InputError message={errors.base_unit_id} />
            </div>

            <div class="grid gap-2">
                <Label for="factor">Conversion factor</Label>
                <Input
                    id="factor"
                    name="factor"
                    type="number"
                    step="0.000001"
                    min="0.000001"
                    value={factor}
                    required
                />
                <p class="text-xs text-muted-foreground">
                    How many base units this unit contains.
                </p>
                <InputError message={errors.factor} />
            </div>

            <label class="flex items-center gap-3 md:col-span-2">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    checked={unit?.is_active ?? true}
                    class="size-4 rounded border-input accent-primary"
                />
                <span class="text-sm font-medium">Active</span>
            </label>
        </div>

        <div class="mt-6 flex items-center gap-2">
            <Button type="submit" disabled={processing}>
                {processing ? 'Saving…' : submitLabel}
            </Button>

            <Button variant="ghost" asChild>
                {#snippet children(props)}
                    <Link {...props} href={cancelHref} class={props.class}>
                        Cancel
                    </Link>
                {/snippet}
            </Button>
        </div>
    {/snippet}
</Form>
