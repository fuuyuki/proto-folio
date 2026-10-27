<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Textarea } from '@/components/ui/textarea';
    import type { Customer } from '@/types';

    let {
        action,
        method,
        customer,
        cancelHref,
        submitLabel,
    }: {
        action: string;
        method: 'post' | 'put';
        customer?: Customer;
        cancelHref: string;
        submitLabel: string;
    } = $props();
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
                    value={customer?.name ?? ''}
                    required
                />
                <InputError message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    name="email"
                    type="email"
                    value={customer?.email ?? ''}
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input
                    id="phone"
                    name="phone"
                    type="tel"
                    value={customer?.phone ?? ''}
                />
                <InputError message={errors.phone} />
            </div>

            <div class="grid gap-2 md:col-span-2">
                <Label for="address">Address</Label>
                <Textarea
                    id="address"
                    name="address"
                    value={customer?.address ?? ''}
                    rows={3}
                />
                <InputError message={errors.address} />
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
