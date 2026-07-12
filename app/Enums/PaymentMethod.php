<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'CASH';
    case CARD = 'CARD';
    case TRANSFER = 'TRANSFER';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Efectivo',
            self::CARD => 'Tarjeta',
            self::TRANSFER => 'Transferencia',
            self::OTHER => 'Otro',
        };
    }
}
