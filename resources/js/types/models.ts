export type Customer = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    invoices_count?: number;
    created_at: string;
    updated_at: string;
};

export type ProductType = 'product' | 'service';

export type CurrencyConfig = {
    code: string;
    symbol: string;
    decimal_places: number;
};

export type Product = {
    id: number;
    name: string;
    sku: string;
    barcode: string | null;
    type: ProductType;
    price: string;
    cost_price: string;
    description: string | null;
    category_id: number | null;
    unit_id: number | null;
    tax_rate_id: number | null;
    track_stock: boolean;
    is_active: boolean;
    category?: { id: number; name: string } | null;
    invoice_items_count?: number;
    created_at: string;
    updated_at: string;
};

export type CategoryOption = {
    id: number;
    name: string;
};

export type UnitOption = {
    id: number;
    name: string;
    code: string;
};

export type TaxRateOption = {
    id: number;
    name: string;
    rate: string;
};

export type ProductCategory = {
    id: number;
    parent_id: number | null;
    name: string;
    slug: string;
    sort_order: number;
    is_active: boolean;
    products_count?: number;
    parent?: { id: number; name: string } | null;
    created_at: string;
    updated_at: string;
};

export type Unit = {
    id: number;
    name: string;
    code: string;
    base_unit_id: number | null;
    factor: string;
    is_active: boolean;
    products_count?: number;
    base_unit?: { id: number; name: string; code: string } | null;
    created_at: string;
    updated_at: string;
};

export type TaxRate = {
    id: number;
    name: string;
    rate: string;
    is_inclusive: boolean;
    is_active: boolean;
    products_count?: number;
    created_at: string;
    updated_at: string;
};

export type InvoiceStatus = 'draft' | 'sent' | 'paid';

export type Invoice = {
    id: number;
    customer_id: number;
    number: string;
    status: InvoiceStatus;
    issue_date: string;
    due_date: string | null;
    subtotal: string;
    tax_amount: string;
    total_amount: string;
    notes: string | null;
    items_count?: number;
    customer?: {
        id: number;
        name: string;
        email?: string | null;
        phone?: string | null;
        address?: string | null;
    } | null;
    created_at: string;
    updated_at: string;
};

export type InvoiceLine = {
    product_id: number | null;
    quantity: string;
    unit_price: string;
    tax_rate: string;
};

export type InvoiceLineOutput = InvoiceLine & {
    id: number;
    description: string;
    line_total: string;
};

export type InvoiceProductOption = {
    id: number;
    name: string;
    sku: string;
    price: string;
    tax_rate: number;
};

export type CustomerOption = {
    id: number;
    name: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
