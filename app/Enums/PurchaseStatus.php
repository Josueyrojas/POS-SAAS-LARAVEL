<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case PENDING = 'PENDING';
    case RECEIVED = 'RECEIVED';
    case CANCELED = 'CANCELED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::RECEIVED => 'Recibida',
            self::CANCELED => 'Cancelada',
        };
    }

    /** Clases Tailwind del badge (mismo sistema de color semántico que BusinessStatus). */
    public function badge(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
            self::RECEIVED => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            self::CANCELED => 'bg-slate-100 text-slate-500 ring-1 ring-inset ring-slate-500/20',
        };
    }
}
