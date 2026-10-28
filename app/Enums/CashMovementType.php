<?php

namespace App\Enums;

enum CashMovementType: string
{
    case In = 'in';
    case Out = 'out';

    /**
     * Human readable label for the movement type.
     */
    public function label(): string
    {
        return match ($this) {
            self::In => 'Cash in',
            self::Out => 'Cash out',
        };
    }
}
