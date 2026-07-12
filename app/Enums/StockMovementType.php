<?php

namespace App\Enums;

enum StockMovementType: string
{
    case MANUAL_IN = 'MANUAL_IN';
    case MANUAL_OUT = 'MANUAL_OUT';
    case SALE = 'SALE';
    case SALE_VOID = 'SALE_VOID';
    case PURCHASE = 'PURCHASE';
    case INITIAL = 'INITIAL';
    case ADJUSTMENT = 'ADJUSTMENT';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL_IN => 'Entrada manual',
            self::MANUAL_OUT => 'Salida manual',
            self::SALE => 'Venta',
            self::SALE_VOID => 'Anulación de venta',
            self::PURCHASE => 'Compra recibida',
            self::INITIAL => 'Stock inicial',
            self::ADJUSTMENT => 'Ajuste',
        };
    }
}
