<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Held = 'held';
    case Completed = 'completed';
    case Voided = 'voided';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    /**
     * Human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Held => 'Held',
            self::Completed => 'Completed',
            self::Voided => 'Voided',
            self::PartiallyRefunded => 'Partially refunded',
            self::Refunded => 'Refunded',
        };
    }
}
