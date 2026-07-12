<?php

namespace App\Enums;

enum PriceType: string
{
    case RETAIL = 'RETAIL';
    case WHOLESALE = 'WHOLESALE';

    public function label(): string
    {
        return match ($this) {
            self::RETAIL => 'Menudeo',
            self::WHOLESALE => 'Mayoreo',
        };
    }
}
