<?php

namespace App\Enums;

enum SaleStatus: string
{
    case COMPLETED = 'COMPLETED';
    case REFUNDED = 'REFUNDED';
    case VOIDED = 'VOIDED';

    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Completada',
            self::REFUNDED => 'Reembolsada',
            self::VOIDED => 'Anulada',
        };
    }

    /** Clases Tailwind del badge (mismo sistema de color semántico que BusinessStatus). */
    public function badge(): string
    {
        return match ($this) {
            self::COMPLETED => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            self::REFUNDED => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
            self::VOIDED => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
        };
    }
}
