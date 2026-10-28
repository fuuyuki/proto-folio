import { page } from '@inertiajs/svelte';
import type { CurrencyConfig } from '@/types/models';

const FALLBACK_CURRENCY: CurrencyConfig = {
    code: 'IDR',
    symbol: 'Rp',
    decimal_places: 0,
};

/**
 * Locales used for grouping separators and symbol placement. Any currency
 * missing from this map falls back to en-US.
 */
const localeByCurrency: Record<string, string> = {
    IDR: 'id-ID',
    USD: 'en-US',
    EUR: 'de-DE',
    GBP: 'en-GB',
    SGD: 'en-SG',
    MYR: 'ms-MY',
    JPY: 'ja-JP',
    CNY: 'zh-CN',
    AUD: 'en-AU',
    INR: 'en-IN',
};

const formatters = new Map<string, Intl.NumberFormat>();

export function currentCurrency(): CurrencyConfig {
    return page.props.currency ?? FALLBACK_CURRENCY;
}

function formatterFor(config: CurrencyConfig): Intl.NumberFormat {
    const key = `${config.code}:${config.decimal_places}`;

    let formatter = formatters.get(key);

    if (!formatter) {
        formatter = new Intl.NumberFormat(
            localeByCurrency[config.code] ?? 'en-US',
            {
                style: 'currency',
                currency: config.code,
                minimumFractionDigits: config.decimal_places,
                maximumFractionDigits: config.decimal_places,
            },
        );

        formatters.set(key, formatter);
    }

    return formatter;
}

export function formatMoney(value: string | number | null | undefined): string {
    const amount =
        typeof value === 'number' ? value : Number.parseFloat(value ?? '');

    return formatterFor(currentCurrency()).format(
        Number.isFinite(amount) ? amount : 0,
    );
}
