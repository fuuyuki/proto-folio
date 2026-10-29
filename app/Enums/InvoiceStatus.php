<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';

    /**
     * Human readable label for the status.
     */
    public function label(): string
    {
        return __('invoice.statuses.'.$this->value);
    }
}
