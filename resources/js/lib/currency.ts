export const CURRENCY = 'USD';

const formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: CURRENCY,
});

export function formatMoney(value: string | number): string {
    const amount = typeof value === 'number' ? value : Number.parseFloat(value);

    return formatter.format(Number.isFinite(amount) ? amount : 0);
}
