<?php

namespace App\Models;

use App\Enums\CashSessionStatus;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'branch_id', 'user_id', 'status',
        'opening_amount', 'opening_at', 'closing_amount', 'expected_amount',
        'difference', 'notes', 'closed_at',
    ];

    protected $casts = [
        'status' => CashSessionStatus::class,
        'opening_amount' => 'decimal:2',
        'opening_at' => 'datetime',
        'closing_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }

    /**
     * Efectivo esperado en caja: fondo inicial + ventas en efectivo que
     * siguen COMPLETED. Una venta en efectivo anulada/reembolsada dentro del
     * mismo turno deja de sumar sola (ya no está COMPLETED) — no se resta
     * aparte, porque el dinero entró y salió, neto cero. Si el reembolso pasa
     * en un turno distinto al de la venta original, ese caso no se refleja
     * aquí (no se reasigna a qué turno pertenece la devolución) — aceptable
     * para esta primera versión, no se persigue perfección contable.
     */
    public function cashExpected(): float
    {
        $cashSales = $this->sales()
            ->where('payment_method', PaymentMethod::CASH->value)
            ->where('status', SaleStatus::COMPLETED->value)
            ->sum('total');

        return round((float) $this->opening_amount + (float) $cashSales, 2);
    }
}
