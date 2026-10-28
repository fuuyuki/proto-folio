<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Transfer = 'transfer';
    case Mobile = 'mobile';
    case StoreCredit = 'store_credit';
    case Other = 'other';

    /**
     * Human readable label for the payment method type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Transfer => 'Bank transfer',
            self::Mobile => 'Mobile payment',
            self::StoreCredit => 'Store credit',
            self::Other => 'Other',
        };
    }
}
