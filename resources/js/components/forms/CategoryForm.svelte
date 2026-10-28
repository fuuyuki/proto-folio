<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { NativeSelect } from '@/components/ui/native-select';
    import type { CategoryOption, ProductCategory } from '@/types';

    let {
        action,
        method,
        category,
        categories = [],
        cancelHref,
        submitLabel,
    }: {
        action: string;
        method: 'post' | 'put';
        category?: ProductCategory;
        categories?: CategoryOption[];
        cancelHref: string;
        submitLabel: string;
    } = $props();

    const parentOptions = $derived(
        categories.filter((option) => option.id !== category?.id),
    );
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
                    value={category?.name ?? ''}
                    required
                />
                <InputError message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    name="slug"
                    value={category?.slug ?? ''}
                    placeholder="auto-generated"
                />
                <p class="text-xs text-muted-foreground">
                    Used in URLs. Leave blank to derive it from the name.
                </p>
                <InputError message={errors.slug} />
            </div>

            <div class="grid gap-2">
                <Label for="parent_id">Parent category</Label>
                <NativeSelect
                    id="parent_id"
                    name="parent_id"
                    value={category?.parent_id?.toString() ?? ''}
                >
                    <option value="">No parent</option>
                    {#each parentOptions as option (option.id)}
                        <option value={option.id}>{option.name}</option>
                    {/each}
                </NativeSelect>
                <InputError message={errors.parent_id} />
            </div>

            <div class="grid gap-2">
                <Label for="sort_order">Sort order</Label>
                <Input
                    id="sort_order"
                    name="sort_order"
                    type="number"
                    min="0"
                    value={category?.sort_order ?? 0}
                />
                <InputError message={errors.sort_order} />
            </div>

            <label class="flex items-center gap-3 self-end pb-2 md:self-center">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    checked={category?.is_active ?? true}
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
