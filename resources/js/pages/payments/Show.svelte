<script lang="ts">
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

    let {
        business,
        invoice,
        paid,
        qris_data_uri,
        qris_error,
    }: {
        business: Business;
        invoice: InvoiceSummary;
        paid: boolean;
        qris_data_uri: string | null;
        qris_error: string | null;
    } = $props();

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

            <dl class="mt-6 grid gap-3 border-t pt-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Customer</dt>
                    <dd>{invoice.customer_name ?? '—'}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Status</dt>
                    <dd>{invoice.status_label}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Issued</dt>
                    <dd>{issued}</dd>
                </div>
            </dl>

            <p class="mt-6 text-xs leading-relaxed text-muted-foreground">
                Keep this page open until your payment is confirmed. If the QR
                does not work, contact us with invoice {invoice.number}.
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
