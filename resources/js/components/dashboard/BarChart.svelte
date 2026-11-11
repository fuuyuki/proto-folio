<script lang="ts">
    import { formatMoney } from '@/lib/currency';

    let {
        points,
        ariaLabel,
    }: {
        points: { label: string; total: number }[];
        ariaLabel: string;
    } = $props();

    const peak = $derived(Math.max(...points.map((point) => point.total), 0));

    function height(total: number): string {
        if (peak <= 0) {
            return '0%';
        }

        return `${Math.max((total / peak) * 100, 1.5)}%`;
    }
</script>

<div class="space-y-2">
    <div
        class="flex h-40 items-end gap-1 border-b border-border"
        role="img"
        aria-label={ariaLabel}
    >
        {#each points as point (point.label)}
            <div
                class="min-h-0.5 flex-1 rounded-t bg-chart-1/70 transition-colors hover:bg-chart-1"
                style={`height: ${height(point.total)}`}
                title={`${point.label} · ${formatMoney(point.total)}`}
            ></div>
        {/each}
    </div>

    <div class="flex justify-between text-xs text-muted-foreground">
        <span>{points[0]?.label ?? ''}</span>
        <span>{points[points.length - 1]?.label ?? ''}</span>
    </div>
</div>
