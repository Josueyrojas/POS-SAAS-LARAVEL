<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'CASH';
    case CARD = 'CARD';
    case TRANSFER = 'TRANSFER';
    case CREDIT = 'CREDIT'; // fiado: exige cliente real, ver SaleController::store()
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Efectivo',
            self::CARD => 'Tarjeta',
            self::TRANSFER => 'Transferencia',
            self::CREDIT => 'Fiado (crédito)',
            self::OTHER => 'Otro',
        };
    }
}
