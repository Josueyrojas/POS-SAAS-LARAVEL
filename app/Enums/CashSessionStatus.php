<?php

namespace App\Enums;

enum CashSessionStatus: string
{
    case OPEN = 'OPEN';
    case CLOSED = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Abierto',
            self::CLOSED => 'Cerrado',
        };
    }

    /** Clases Tailwind del badge (mismo sistema de color semántico que BusinessStatus). */
    public function badge(): string
    {
        return match ($this) {
            self::OPEN => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            self::CLOSED => 'bg-slate-100 text-slate-500 ring-1 ring-inset ring-slate-500/20',
        };
    }
}
