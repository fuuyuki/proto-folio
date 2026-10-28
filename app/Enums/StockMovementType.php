<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Return = 'return';
    case Adjustment = 'adjustment';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Initial = 'initial';

    /**
     * Human readable label for the movement type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::Sale => 'Sale',
            self::Return => 'Return',
            self::Adjustment => 'Adjustment',
            self::TransferIn => 'Transfer in',
            self::TransferOut => 'Transfer out',
            self::Initial => 'Initial stock',
        };
    }
}
