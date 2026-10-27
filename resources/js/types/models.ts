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

export type Product = {
    id: number;
    name: string;
    sku: string;
    price: string;
    description: string | null;
    invoice_items_count?: number;
    created_at: string;
    updated_at: string;
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
