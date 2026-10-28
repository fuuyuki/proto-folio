<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { NativeSelect } from '@/components/ui/native-select';
    import { Textarea } from '@/components/ui/textarea';
    import type {
        CategoryOption,
        Product,
        TaxRateOption,
        UnitOption,
    } from '@/types';

    let {
        action,
        method,
        product,
        cancelHref,
        submitLabel,
        categories = [],
        units = [],
        taxRates = [],
    }: {
        action: string;
        method: 'post' | 'put';
        product?: Product;
        cancelHref: string;
        submitLabel: string;
        categories?: CategoryOption[];
        units?: UnitOption[];
        taxRates?: TaxRateOption[];
    } = $props();

    let type = $state(untrack(() => product?.type ?? 'product'));
    const tracksStock = $derived(type !== 'service');
</script>

<Form {action} {method} options={{ preserveScroll: true }}>
    {#snippet children({ errors, processing })}
        <div class="space-y-6">
            <section class="rounded-xl border border-border bg-card p-6">
                <div class="mb-5 space-y-0.5">
                    <h2 class="text-sm font-semibold">Product details</h2>
                    <p class="text-xs text-muted-foreground">
                        How the item appears on receipts and invoices.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            value={product?.name ?? ''}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="type">Type</Label>
                        <NativeSelect id="type" name="type" bind:value={type}>
                            <option value="product">Product</option>
                            <option value="service">Service</option>
                        </NativeSelect>
                        <InputError message={errors.type} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="sku">SKU</Label>
                        <Input
                            id="sku"
                            name="sku"
                            value={product?.sku ?? ''}
                            placeholder="Auto-generated"
                        />
                        <p class="text-xs text-muted-foreground">
                            Leave blank to generate one from the product
                            number format.
                        </p>
                        <InputError message={errors.sku} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="barcode">Barcode</Label>
                        <Input
                            id="barcode"
                            name="barcode"
                            value={product?.barcode ?? ''}
                        />
                        <InputError message={errors.barcode} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="category_id">Category</Label>
                        <NativeSelect
                            id="category_id"
                            name="category_id"
                            value={product?.category_id?.toString() ?? ''}
                        >
                            <option value="">No category</option>
                            {#each categories as category (category.id)}
                                <option value={category.id}>
                                    {category.name}
                                </option>
                            {/each}
                        </NativeSelect>
                        <InputError message={errors.category_id} />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            value={product?.description ?? ''}
                            rows={3}
                        />
                        <InputError message={errors.description} />
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-border bg-card p-6">
                <div class="mb-5 space-y-0.5">
                    <h2 class="text-sm font-semibold">Pricing & stock</h2>
                    <p class="text-xs text-muted-foreground">
                        Prices are in your base currency. Services are never
                        stock tracked.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="price">Selling price</Label>
                        <Input
                            id="price"
                            name="price"
                            type="number"
                            step="0.01"
                            min="0"
                            value={product?.price ?? ''}
                            required
                        />
                        <InputError message={errors.price} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="cost_price">Cost price</Label>
                        <Input
                            id="cost_price"
                            name="cost_price"
                            type="number"
                            step="0.01"
                            min="0"
                            value={product?.cost_price ?? ''}
                        />
                        <InputError message={errors.cost_price} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tax_rate_id">Tax rate</Label>
                        <NativeSelect
                            id="tax_rate_id"
                            name="tax_rate_id"
                            value={product?.tax_rate_id?.toString() ?? ''}
                        >
                            <option value="">No tax</option>
                            {#each taxRates as taxRate (taxRate.id)}
                                <option value={taxRate.id}>
                                    {taxRate.name} ({Number.parseFloat(
                                        taxRate.rate,
                                    )}%)
                                </option>
                            {/each}
                        </NativeSelect>
                        <InputError message={errors.tax_rate_id} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="unit_id">Unit</Label>
                        <NativeSelect
                            id="unit_id"
                            name="unit_id"
                            value={product?.unit_id?.toString() ?? ''}
                        >
                            <option value="">No unit</option>
                            {#each units as unit (unit.id)}
                                <option value={unit.id}>
                                    {unit.name} ({unit.code})
                                </option>
                            {/each}
                        </NativeSelect>
                        <InputError message={errors.unit_id} />
                    </div>

                    <label class="flex items-start gap-3 md:col-span-2">
                        <input
                            type="checkbox"
                            name="track_stock"
                            value="1"
                            checked={product?.track_stock ?? true}
                            disabled={!tracksStock}
                            class="mt-0.5 size-4 rounded border-input accent-primary disabled:opacity-50"
                        />
                        <span class="space-y-0.5">
                            <span class="block text-sm font-medium">
                                Track stock
                            </span>
                            <span class="block text-xs text-muted-foreground">
                                Keep a running quantity for this item. Disabled
                                for services.
                            </span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 md:col-span-2">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            checked={product?.is_active ?? true}
                            class="mt-0.5 size-4 rounded border-input accent-primary"
                        />
                        <span class="space-y-0.5">
                            <span class="block text-sm font-medium">Active</span>
                            <span class="block text-xs text-muted-foreground">
                                Inactive products stay on past sales but cannot
                                be added to new ones.
                            </span>
                        </span>
                    </label>
                </div>
            </section>
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
