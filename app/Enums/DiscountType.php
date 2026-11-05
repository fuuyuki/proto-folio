<?php

namespace App\Enums;

enum DiscountType: string
{
    case None = 'none';
    case Percent = 'percent';
    case Amount = 'amount';
}
