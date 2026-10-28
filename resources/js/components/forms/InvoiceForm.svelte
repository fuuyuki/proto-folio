<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import { untrack } from 'svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { NativeSelect } from '@/components/ui/native-select';
    import { Textarea } from '@/components/ui/textarea';
    import { formatMoney } from '@/lib/currency';
    import type {
        CustomerOption,
        Invoice,
        InvoiceLine,
        InvoiceProductOption,
        InvoiceStatus,
    } from '@/types';

    type Line = {
        key: number;
        product_id: string;
        quantity: string;
        unit_price: string;
        tax_rate: string;
    };

    let {
        action,
        method,
        invoice,
        items = [],
        customers = [],
        products = [],
        cancelHref,
        submitLabel,
    }: {
        action: string;
        method: 'post' | 'put';
        invoice?: Invoice;
        items?: InvoiceLine[];
        customers?: CustomerOption[];
        products?: InvoiceProductOption[];
        cancelHref: string;
        submitLabel: string;
    } = $props();

    let nextKey = 0;

    function toLine(item?: InvoiceLine): Line {
        return {
            key: nextKey++,
            product_id: item?.product_id?.toString() ?? '',
            quantity: item?.quantity ?? '1',
            unit_price: item?.unit_price ?? '',
            tax_rate: item?.tax_rate ?? '0',
        };
    }

    let lines = $state<Line[]>(
        untrack(() => (items.length > 0 ? items.map(toLine) : [toLine()])),
    );

    let status = $state<InvoiceStatus>(
        untrack(() => invoice?.status ?? 'draft'),
    );

    const today = new Date().toLocaleDateString('sv-SE');

    const subtotal = $derived(
        lines.reduce((sum, line) => sum + lineAmount(line), 0),
    );
    const taxAmount = $derived(
        lines.reduce(
            (sum, line) =>
                sum + (lineAmount(line) * parseRate(line.tax_rate)) / 100,
            0,
        ),
    );
    const total = $derived(subtotal + taxAmount);

    function parseRate(value: string): number {
        const rate = Number.parseFloat(value);

        return Number.isFinite(rate) ? rate : 0;
    }

    function lineAmount(line: Line): number {
        const quantity = Number.parseFloat(line.quantity);
        const price = Number.parseFloat(line.unit_price);

        if (!Number.isFinite(quantity) || !Number.isFinite(price)) {
            return 0;
        }

        return quantity * price;
    }

    function addLine(): void {
        lines.push(toLine());
    }

    function removeLine(key: number): void {
        if (lines.length === 1) {
            return;
        }

        lines = lines.filter((line) => line.key !== key);
    }

    function applyProduct(index: number, productId: string): void {
        const product = products.find(
            (candidate) => candidate.id.toString() === productId,
        );

        if (!product) {
            return;
        }

        lines[index].unit_price = product.price;
        lines[index].tax_rate = product.tax_rate.toString();
    }
</script>

<Form {action} {method} options={{ preserveScroll: true }}>
    {#snippet children({ errors, processing })}
        <div class="space-y-6">
            <section class="rounded-xl border border-border bg-card p-6">
                <div class="mb-5 space-y-0.5">
                    <h2 class="text-sm font-semibold">Invoice details</h2>
                    <p class="text-xs text-muted-foreground">
                        The invoice number is generated automatically when the
                        invoice is saved.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="customer_id">Customer</Label>
                        <NativeSelect
                            id="customer_id"
                            name="customer_id"
                            value={invoice?.customer_id?.toString() ?? ''}
                            required
                        >
                            <option value="" disabled>Select a customer</option>
                            {#each customers as customer (customer.id)}
                                <option value={customer.id}>
                                    {customer.name}
                                </option>
                            {/each}
                        </NativeSelect>
                        <InputError message={errors.customer_id} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <NativeSelect id="status" name="status" bind:value={status}>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="paid">Paid</option>
                        </NativeSelect>
                        <InputError message={errors.status} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="issue_date">Issue date</Label>
                        <Input
                            id="issue_date"
                            name="issue_date"
                            type="date"
                            value={invoice?.issue_date?.slice(0, 10) ?? today}
                            required
                        />
                        <InputError message={errors.issue_date} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="due_date">Due date</Label>
                        <Input
                            id="due_date"
                            name="due_date"
                            type="date"
                            value={invoice?.due_date?.slice(0, 10) ?? ''}
                        />
                        <InputError message={errors.due_date} />
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-border bg-card p-6">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-0.5">
                        <h2 class="text-sm font-semibold">Line items</h2>
                        <p class="text-xs text-muted-foreground">
                            Choose a product to fill its price and tax rate, or
                            type them in manually.
                        </p>
                    </div>

                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        onclick={addLine}
                    >
                        <Plus class="size-4" />
                        Add item
                    </Button>
                </div>

                <div class="space-y-4">
                    {#each lines as line, index (line.key)}
                        <div
                            class="grid gap-3 border-b border-border pb-4 md:grid-cols-12 md:items-end"
                        >
                            <div class="grid gap-1.5 md:col-span-4">
                                <Label
                                    for={`items-${line.key}-product_id`}
                                    class="text-xs text-muted-foreground"
                                >
                                    Item
                                </Label>
                                <NativeSelect
                                    id={`items-${line.key}-product_id`}
                                    name={`items[${index}][product_id]`}
                                    bind:value={line.product_id}
                                    onchange={(event) =>
                                        applyProduct(
                                            index,
                                            event.currentTarget.value,
                                        )}
                                >
                                    <option value="">Custom item</option>
                                    {#each products as product (product.id)}
                                        <option value={product.id}>
                                            {product.name}
                                            {#if product.sku}({product.sku}){/if}
                                        </option>
                                    {/each}
                                </NativeSelect>
                            </div>

                            <div class="grid gap-1.5 md:col-span-2">
                                <Label
                                    for={`items-${line.key}-quantity`}
                                    class="text-xs text-muted-foreground"
                                >
                                    Qty
                                </Label>
                                <Input
                                    id={`items-${line.key}-quantity`}
                                    name={`items[${index}][quantity]`}
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    bind:value={line.quantity}
                                    required
                                />
                                <InputError
                                    message={errors[`items.${index}.quantity`]}
                                />
                            </div>

                            <div class="grid gap-1.5 md:col-span-2">
                                <Label
                                    for={`items-${line.key}-unit_price`}
                                    class="text-xs text-muted-foreground"
                                >
                                    Unit price
                                </Label>
                                <Input
                                    id={`items-${line.key}-unit_price`}
                                    name={`items[${index}][unit_price]`}
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    bind:value={line.unit_price}
                                    required
                                />
                                <InputError
                                    message={errors[`items.${index}.unit_price`]}
                                />
                            </div>

                            <div class="grid gap-1.5 md:col-span-1">
                                <Label
                                    for={`items-${line.key}-tax_rate`}
                                    class="text-xs text-muted-foreground"
                                >
                                    Tax %
                                </Label>
                                <Input
                                    id={`items-${line.key}-tax_rate`}
                                    name={`items[${index}][tax_rate]`}
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    bind:value={line.tax_rate}
                                />
                            </div>

                            <div
                                class="grid gap-1.5 text-right md:col-span-2"
                            >
                                <span
                                    class="text-xs text-muted-foreground"
                                >
                                    Amount
                                </span>
                                <span class="py-2 text-sm tabular-nums">
                                    {formatMoney(lineAmount(line))}
                                </span>
                            </div>

                            <div class="flex md:col-span-1 md:justify-end">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    disabled={lines.length === 1}
                                    aria-label="Remove item"
                                    onclick={() => removeLine(line.key)}
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </div>
                    {/each}
                </div>

                <InputError message={errors.items} class="mt-3" />

                <div class="mt-6 flex justify-end">
                    <dl class="w-full max-w-xs space-y-1.5 text-sm">
                        <div class="flex items-center justify-between gap-6">
                            <dt class="text-muted-foreground">Subtotal</dt>
                            <dd class="tabular-nums">{formatMoney(subtotal)}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-6">
                            <dt class="text-muted-foreground">Tax</dt>
                            <dd class="tabular-nums">{formatMoney(taxAmount)}</dd>
                        </div>
                        <div
                            class="flex items-center justify-between gap-6 border-t border-border pt-1.5 font-semibold"
                        >
                            <dt>Total</dt>
                            <dd class="tabular-nums">{formatMoney(total)}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="rounded-xl border border-border bg-card p-6">
                <div class="grid gap-2">
                    <Label for="notes">Notes</Label>
                    <Textarea
                        id="notes"
                        name="notes"
                        value={invoice?.notes ?? ''}
                        rows={3}
                        placeholder="Payment terms, thank you note, or anything that should appear on the invoice."
                    />
                    <InputError message={errors.notes} />
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
