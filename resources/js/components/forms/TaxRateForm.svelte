<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { TaxRate } from '@/types';

    let {
        action,
        method,
        taxRate,
        cancelHref,
        submitLabel,
    }: {
        action: string;
        method: 'post' | 'put';
        taxRate?: TaxRate;
        cancelHref: string;
        submitLabel: string;
    } = $props();

    const rate = $derived(
        taxRate === undefined
            ? ''
            : String(Number.parseFloat(taxRate.rate)),
    );
</script>

<Form {action} {method} options={{ preserveScroll: true }}>
    {#snippet children({ errors, processing })}
        <div
            class="grid gap-5 rounded-xl border border-border bg-card p-6 md:grid-cols-2"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    value={taxRate?.name ?? ''}
                    placeholder="PPN"
                    required
                />
                <InputError message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="rate">Rate (%)</Label>
                <Input
                    id="rate"
                    name="rate"
                    type="number"
                    step="0.001"
                    min="0"
                    max="100"
                    value={rate}
                    required
                />
                <InputError message={errors.rate} />
            </div>

            <label class="flex items-start gap-3 md:col-span-2">
                <input
                    type="checkbox"
                    name="is_inclusive"
                    value="1"
                    checked={taxRate?.is_inclusive ?? false}
                    class="mt-0.5 size-4 rounded border-input accent-primary"
                />
                <span class="space-y-0.5">
                    <span class="block text-sm font-medium">Tax inclusive</span>
                    <span class="block text-xs text-muted-foreground">
                        Prices already include this tax. Leave off when tax is
                        added on top.
                    </span>
                </span>
            </label>

            <label class="flex items-center gap-3 md:col-span-2">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    checked={taxRate?.is_active ?? true}
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
