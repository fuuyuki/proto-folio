<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Textarea } from '@/components/ui/textarea';
    import type { Product } from '@/types';

    let {
        action,
        method,
        product,
        cancelHref,
        submitLabel,
    }: {
        action: string;
        method: 'post' | 'put';
        product?: Product;
        cancelHref: string;
        submitLabel: string;
    } = $props();
</script>

<Form {action} {method} options={{ preserveScroll: true }}>
    {#snippet children({ errors, processing })}
        <div
            class="grid gap-5 rounded-xl border border-border bg-card p-6 md:grid-cols-2"
        >
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
                <Label for="sku">SKU</Label>
                <Input id="sku" name="sku" value={product?.sku ?? ''} required />
                <InputError message={errors.sku} />
            </div>

            <div class="grid gap-2">
                <Label for="price">Price</Label>
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
