<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';

    let {
        action,
        title,
        description,
        confirmLabel = 'Delete',
        disabled = false,
        disabledReason = '',
    }: {
        action: string;
        title: string;
        description: string;
        confirmLabel?: string;
        disabled?: boolean;
        disabledReason?: string;
    } = $props();

    let open = $state(false);
</script>

{#if disabled}
    <Button
        variant="ghost"
        size="icon"
        disabled
        title={disabledReason}
        class="text-muted-foreground"
    >
        <Trash2 class="size-4" />
    </Button>
{:else}
    <Button
        variant="ghost"
        size="icon"
        class="text-muted-foreground hover:text-destructive"
        aria-label={title}
        onclick={() => (open = true)}
    >
        <Trash2 class="size-4" />
    </Button>

    <Dialog bind:open>
        <DialogContent>
            <Form {action} method="delete" options={{ preserveScroll: true }}>
                {#snippet children({ processing })}
                    <div class="space-y-2">
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </div>

                    <DialogFooter class="mt-6 gap-2">
                        <Button
                            variant="secondary"
                            onclick={() => (open = false)}
                        >
                            Cancel
                        </Button>

                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={processing}
                        >
                            {processing ? 'Deleting…' : confirmLabel}
                        </Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        </DialogContent>
    </Dialog>
{/if}
