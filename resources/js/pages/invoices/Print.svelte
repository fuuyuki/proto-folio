<script lang="ts">
    import Printer from '@lucide/svelte/icons/printer';
    import { onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { formatMoney } from '@/lib/currency';
    import { show } from '@/routes/invoices';
    import type { Invoice, InvoiceLineOutput } from '@/types';

    let {
        invoice,
        items,
        company,
    }: {
        invoice: Invoice;
        items: InvoiceLineOutput[];
        company: string;
    } = $props();

    onMount(() => {
        const timer = setTimeout(() => window.print(), 400);

        return () => clearTimeout(timer);
    });

    function formatDate(value: string | null): string {
        if (!value) {
            return '—';
        }

        return new Date(value).toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    }

    function formatQuantity(value: string): string {
        return Number.parseFloat(value).toString();
    }

    function lineLabel(item: InvoiceLineOutput): string {
        const rate = Number.parseFloat(item.tax_rate);
        const base = `${formatQuantity(item.quantity)} x ${formatMoney(item.unit_price)}`;

        return rate > 0 ? `${base} · tax ${rate}%` : base;
    }
</script>

<AppHead title={`Receipt ${invoice.number}`} />

<div class="toolbar">
    <button type="button" class="toolbar-button" onclick={() => window.print()}>
        <Printer class="size-4" />
        Print
    </button>

    <a class="toolbar-button" href={show(invoice.id).url}>
        Back to invoice
    </a>
</div>

<article class="receipt">
    <p class="shop">{company}</p>
    <p class="center muted">INVOICE</p>
    <p class="center number">{invoice.number}</p>

    <div class="rule"></div>

    <div class="row">
        <span>Date</span>
        <span>{formatDate(invoice.issue_date)}</span>
    </div>
    {#if invoice.due_date}
        <div class="row">
            <span>Due</span>
            <span>{formatDate(invoice.due_date)}</span>
        </div>
    {/if}
    <div class="row">
        <span>Status</span>
        <span class="upper">{invoice.status}</span>
    </div>

    <div class="rule"></div>

    <p class="muted">Billed to</p>
    <p class="strong">{invoice.customer?.name ?? '—'}</p>
    {#if invoice.customer?.phone}
        <p>{invoice.customer.phone}</p>
    {/if}
    {#if invoice.customer?.address}
        <p class="pre">{invoice.customer.address}</p>
    {/if}

    <div class="rule"></div>

    {#each items as item (item.id)}
        <div class="item">
            <p class="strong">{item.description}</p>
            <div class="row">
                <span>{lineLabel(item)}</span>
                <span>{formatMoney(item.line_total)}</span>
            </div>
        </div>
    {/each}

    <div class="rule"></div>

    <div class="row">
        <span>Subtotal</span>
        <span>{formatMoney(invoice.subtotal)}</span>
    </div>
    <div class="row">
        <span>Tax</span>
        <span>{formatMoney(invoice.tax_amount)}</span>
    </div>
    <div class="row total">
        <span>TOTAL</span>
        <span>{formatMoney(invoice.total_amount)}</span>
    </div>

    {#if invoice.notes}
        <div class="rule"></div>
        <p class="note">{invoice.notes}</p>
    {/if}

    <div class="rule"></div>
    <p class="center muted">Thank you</p>

    <!-- Paper feed so the tear bar / cutter clears the printed text. -->
    <div class="feed" aria-hidden="true"></div>
</article>

<style>
    @page {
        size: 58mm auto;
        margin: 0;
    }

    @media screen {
        :global(body) {
            margin: 0;
            background: #f3f4f6;
            padding: 24px 0;
        }
    }

    .toolbar {
        position: fixed;
        top: 12px;
        right: 12px;
        display: flex;
        gap: 8px;
    }

    .toolbar-button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #fff;
        padding: 6px 10px;
        font-size: 13px;
        color: #111827;
        text-decoration: none;
        cursor: pointer;
    }

    .toolbar-button:hover {
        background: #f9fafb;
    }

    .receipt {
        width: 58mm;
        box-sizing: border-box;
        margin: 0 auto;
        padding: 3mm 5mm;
        background: #fff;
        color: #000;
        font-family:
            ui-monospace, SFMono-Regular, Menlo, Consolas, 'Liberation Mono',
            monospace;
        font-size: 9.5px;
        line-height: 1.4;
    }

    .shop {
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .number {
        font-size: 10px;
        font-weight: 700;
    }

    .center {
        text-align: center;
    }

    .muted {
        color: #4b5563;
    }

    .strong {
        font-weight: 700;
        word-break: break-word;
    }

    .upper {
        text-transform: uppercase;
    }

    .pre {
        white-space: pre-line;
        word-break: break-word;
    }

    .rule {
        margin: 2mm 0;
        border-top: 1px dashed #000;
    }

    .row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 3mm;
    }

    .row span:last-child {
        text-align: right;
        white-space: nowrap;
    }

    .item {
        margin-bottom: 1.2mm;
    }

    .total {
        margin-top: 1mm;
        padding-top: 1mm;
        border-top: 1px solid #000;
        font-size: 11px;
        font-weight: 700;
    }

    .note {
        white-space: pre-line;
        word-break: break-word;
    }

    .feed {
        height: 12mm;
    }

    @media print {
        :global(body) {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .toolbar {
            display: none;
        }

        .receipt {
            width: 58mm;
            margin: 0;
            padding: 0 5mm;
        }
    }
</style>
