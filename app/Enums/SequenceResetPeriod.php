<?php

namespace App\Enums;

enum SequenceResetPeriod: string
{
    case Never = 'never';
    case Yearly = 'yearly';
    case Monthly = 'monthly';
    case Daily = 'daily';

    /**
     * Human readable label for the reset period.
     */
    public function label(): string
    {
        return match ($this) {
            self::Never => 'Never',
            self::Yearly => 'Yearly',
            self::Monthly => 'Monthly',
            self::Daily => 'Daily',
        };
    }
}
