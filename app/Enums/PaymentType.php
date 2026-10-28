<?php

namespace App\Enums;

enum PaymentType: string
{
    case Payment = 'payment';
    case Refund = 'refund';

    /**
     * Human readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Payment => 'Payment',
            self::Refund => 'Refund',
        };
    }
}
