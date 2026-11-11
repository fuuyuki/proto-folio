<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import { formatMoney } from '@/lib/currency';

    let {
        points,
    }: {
        points: { date: string; total: number }[];
    } = $props();

    const peak = $derived(Math.max(...points.map((point) => point.total), 0));

    function label(date: string | undefined): string {
        if (!date) {
            return '';
        }

        return new Date(`${date}T00:00:00`).toLocaleDateString(
            page.props.locale,
            { day: '2-digit', month: 'short' },
        );
    }

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
        aria-label={`Billed revenue per day, peak ${formatMoney(peak)}`}
    >
        {#each points as point (point.date)}
            <div
                class="min-h-0.5 flex-1 rounded-t bg-chart-1/70 transition-colors hover:bg-chart-1"
                style={`height: ${height(point.total)}`}
                title={`${label(point.date)} · ${formatMoney(point.total)}`}
            ></div>
        {/each}
    </div>

    <div class="flex justify-between text-xs text-muted-foreground">
        <span>{label(points[0]?.date)}</span>
        <span>{label(points[points.length - 1]?.date)}</span>
    </div>
</div>
