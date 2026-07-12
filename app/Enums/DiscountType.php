<?php

namespace App\Enums;

enum DiscountType: string
{
    case PERCENT = 'PERCENT';
    case FIXED = 'FIXED';

    public function label(): string
    {
        return match ($this) {
            self::PERCENT => 'Porcentaje',
            self::FIXED => 'Monto fijo',
        };
    }
}
