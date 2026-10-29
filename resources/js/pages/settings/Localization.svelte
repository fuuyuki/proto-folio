<script module lang="ts">
    import { edit as editLocalization } from '@/routes/localization';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Language settings',
                href: editLocalization(),
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
    import { Label } from '@/components/ui/label';
    import { NativeSelect } from '@/components/ui/native-select';
    import { update } from '@/routes/localization';

    let {
        locale,
        locales,
    }: {
        locale: string;
        locales: Record<string, string>;
    } = $props();
</script>

<AppHead title="Language settings" />

<h1 class="sr-only">Language settings</h1>

<Form action={update().url} method="put" options={{ preserveScroll: true }}>
    {#snippet children({ errors, processing })}
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Language"
                description="Choose the language used across the app and on printed invoices."
            />

            <div class="grid gap-2">
                <Label for="locale">Language</Label>
                <NativeSelect id="locale" name="locale" value={locale}>
                    {#each Object.entries(locales) as [value, label] (value)}
                        <option {value}>{label}</option>
                    {/each}
                </NativeSelect>
                <InputError message={errors.locale} />
            </div>
        </div>

        <div class="mt-6">
            <Button type="submit" disabled={processing}>
                {processing ? 'Saving…' : 'Save'}
            </Button>
        </div>
    {/snippet}
</Form>
