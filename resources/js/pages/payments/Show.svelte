<script lang="ts">
    import { usePoll } from '@inertiajs/svelte';
    import { formatMoney } from '@/lib/currency';

    type Business = {
        name: string;
        address: string | null;
    };

    type InvoiceSummary = {
        number: string;
        status: string;
        status_label: string;
        issue_date: string | null;
        total: number;
        customer_name: string | null;
    };

    type OrderStep = {
        key: string;
        label: string;
        description: string;
    };

    type OrderProgress = {
        status: string;
        label: string;
        fulfillment: 'pickup' | 'shipment' | null;
        steps: OrderStep[];
    };

    let {
        business,
        invoice,
        order,
        paid,
        qris_data_uri,
        qris_error,
    }: {
        business: Business;
        invoice: InvoiceSummary;
        order: OrderProgress;
        paid: boolean;
        qris_data_uri: string | null;
        qris_error: string | null;
    } = $props();

    usePoll(
        15_000,
        { only: ['order', 'paid'] },
        { background: 'pause' },
    );

    const currentStep = $derived(
        Math.max(
            0,
            order.steps.findIndex((step) => step.key === order.status),
        ),
    );

    const issued = $derived(
        invoice.issue_date
            ? new Date(`${invoice.issue_date}T00:00:00`).toLocaleDateString(
                  'id-ID',
                  {
                      day: '2-digit',
                      month: 'short',
                      year: 'numeric',
                  },
              )
            : '—',
    );
</script>

<svelte:head>
    <meta name="robots" content="noindex" />
    <title>Pay {invoice.number} — {business.name}</title>
</svelte:head>

<div class="flex min-h-screen flex-col bg-muted/30">
    <main
        class="mx-auto flex w-full max-w-xl flex-1 flex-col justify-center px-4 py-10 sm:py-14"
    >
        <section class="rounded-2xl border bg-card p-6 shadow-sm sm:p-8">
            <header>
                <p
                    class="text-xs tracking-wide text-muted-foreground uppercase"
                >
                    QRIS payment
                </p>
                <h1 class="mt-2 text-2xl font-semibold">{business.name}</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Invoice {invoice.number}
                </p>
            </header>

            <div
                class="mt-6 flex items-baseline justify-between gap-4 rounded-xl bg-muted/60 p-4"
            >
                <span class="text-sm text-muted-foreground">Amount</span>
                <span class="font-mono text-2xl font-semibold tabular-nums">
                    {formatMoney(invoice.total)}
                </span>
            </div>

            {#if paid}
                <div
                    class="mt-6 rounded-xl border border-status-paid/30 bg-status-paid-soft p-4"
                >
                    <p class="font-medium text-status-paid">Payment received</p>
                    <p class="mt-1 text-sm">
                        This invoice is already paid. Thank you.
                    </p>
                </div>
            {:else if qris_data_uri}
                <div class="mt-6 flex flex-col items-center">
                    <img
                        src={qris_data_uri}
                        alt={`QRIS payment code for invoice ${invoice.number}`}
                        width="720"
                        height="720"
                        class="h-auto w-full max-w-[320px] rounded-xl border bg-white p-3"
                    />
                    <p class="mt-4 text-center text-sm text-muted-foreground">
                        Scan with any QRIS-enabled app. The amount is filled in
                        automatically.
                    </p>
                </div>
            {:else}
                <div
                    class="mt-6 rounded-xl border border-destructive/30 bg-destructive/5 p-4 text-sm"
                >
                    {qris_error ?? 'QRIS is not available for this invoice.'}
                </div>
            {/if}

            <section
                class="mt-6 border-t pt-6"
                data-order-status={order.status}
            >
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold">Order progress</h2>
                        <p
                            class="mt-1 text-sm text-muted-foreground"
                            aria-live="polite"
                        >
                            {order.label}
                        </p>
                    </div>
                    <span
                        class="font-mono text-[10px] tracking-wide text-muted-foreground uppercase"
                    >
                        Updates automatically
                    </span>
                </div>

                <ol class="mt-4 space-y-3">
                    {#each order.steps as step, index (step.key)}
                        {@const state =
                            index < currentStep
                                ? 'done'
                                : index === currentStep
                                  ? 'current'
                                  : 'upcoming'}
                        <li
                            class="flex gap-3"
                            data-step-key={step.key}
                            data-step-state={state}
                        >
                            <span
                                class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full border text-[11px] font-semibold {state ===
                                'done'
                                    ? 'border-status-paid bg-status-paid text-white'
                                    : state === 'current'
                                      ? 'border-primary bg-primary text-primary-foreground'
                                      : 'border-border bg-muted text-muted-foreground'}"
                            >
                                {#if state === 'done'}✓{:else}{index + 1}{/if}
                            </span>
                            <div class="min-w-0">
                                <p
                                    class={state === 'upcoming'
                                        ? 'text-sm text-muted-foreground'
                                        : 'text-sm font-medium'}
                                >
                                    {step.label}
                                </p>
                                <p
                                    class="mt-0.5 text-xs leading-relaxed text-muted-foreground"
                                >
                                    {step.description}
                                </p>
                            </div>
                        </li>
                    {/each}
                </ol>
            </section>

            <dl class="mt-6 grid gap-3 border-t pt-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Customer</dt>
                    <dd>{invoice.customer_name ?? '—'}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Payment</dt>
                    <dd>{paid ? 'Paid' : 'Awaiting payment'}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Issued</dt>
                    <dd>{issued}</dd>
                </div>
            </dl>

            <p class="mt-6 text-xs leading-relaxed text-muted-foreground">
                {#if paid}
                    This page updates automatically as your order moves
                    through production.
                {:else}
                    Keep this page open until your payment is confirmed. If the
                    QR does not work, contact us with invoice {invoice.number}.
                {/if}
            </p>
        </section>

        {#if business.address}
            <p
                class="mt-4 text-center text-xs whitespace-pre-line text-muted-foreground"
            >
                {business.address}
            </p>
        {/if}
    </main>
</div>
