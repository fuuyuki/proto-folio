<script lang="ts">
    import { Badge } from '@/components/ui/badge';
    import type { InvoiceStatus } from '@/types';

    let {
        status,
        label,
    }: {
        status: InvoiceStatus;
        label: string;
    } = $props();

    const classes: Record<InvoiceStatus, string> = {
        draft: 'border-transparent bg-surface-2 text-ink-soft',
        sent: 'border-transparent bg-status-shipped-soft text-status-shipped',
        paid: 'border-transparent bg-status-paid-soft text-status-paid',
    };
</script>

<Badge variant="outline" class={classes[status]}>
    {label}
</Badge>
