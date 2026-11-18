<script module lang="ts">
    import { edit as editBusiness } from '@/routes/business';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Business details',
                href: editBusiness(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Textarea } from '@/components/ui/textarea';
    import { update } from '@/routes/business';
    import type { BusinessProfile } from '@/types';

    let {
        business,
        qris_payload = '',
    }: {
        business: BusinessProfile;
        qris_payload?: string;
    } = $props();
</script>

<AppHead title="Business details" />

<h1 class="sr-only">Business details</h1>

<Form action={update().url} method="put" options={{ preserveScroll: true }}>
    {#snippet children({ errors, processing })}
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Business details"
                description="Shown at the top of every printed invoice receipt."
            />

            <div class="grid gap-2">
                <Label for="name">Business name</Label>
                <Input
                    id="name"
                    name="name"
                    value={business.name}
                    required
                />
                <InputError message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="address">Business address</Label>
                <Textarea
                    id="address"
                    name="address"
                    rows={3}
                    value={business.address ?? ''}
                    placeholder="Street, city, postal code"
                />
                <p class="text-xs text-muted-foreground">
                    Line breaks are kept exactly as typed on the receipt.
                </p>
                <InputError message={errors.address} />
            </div>

            <Heading
                variant="small"
                title="QRIS payments"
                description="Used by the public 3D print order page. Customers scan a QR with the invoice amount already filled in."
            />

            <div class="grid gap-2">
                <Label for="qris_payload">Static QRIS payload</Label>
                <Textarea
                    id="qris_payload"
                    name="qris_payload"
                    rows={6}
                    value={qris_payload}
                    placeholder="000201010211..."
                    spellcheck="false"
                />
                <p class="text-xs text-muted-foreground">
                    Paste the complete static QRIS string from your merchant
                    app. Leave it blank to disable QRIS on new orders.
                </p>
                <InputError message={errors.qris_payload} />
            </div>
        </div>

        <div class="mt-6">
            <Button type="submit" disabled={processing}>
                {processing ? 'Saving…' : 'Save'}
            </Button>
        </div>
    {/snippet}
</Form>
