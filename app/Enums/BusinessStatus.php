<?php

namespace App\Enums;

enum BusinessStatus: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case PENDING = 'PENDING';
    case CANCELED = 'CANCELED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::SUSPENDED => 'Suspendido',
            self::PENDING => 'Pendiente',
            self::CANCELED => 'Cancelado',
        };
    }

    /** Clases Tailwind del badge (sistema de color semántico del panel). */
    public function badge(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            self::PENDING => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
            self::SUSPENDED => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
            self::CANCELED => 'bg-slate-100 text-slate-500 ring-1 ring-inset ring-slate-500/20',
        };
    }

    public function accent(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-emerald-500',
            self::PENDING => 'bg-amber-400',
            self::SUSPENDED => 'bg-rose-500',
            self::CANCELED => 'bg-slate-300',
        };
    }
}
