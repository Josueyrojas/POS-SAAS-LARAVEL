<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kardex: registro inmutable de cada movimiento de stock (venta, compra,
 * ajuste manual, anulación). Toda modificación de products.stock debe pasar
 * por aquí, dentro de una transacción con lockForUpdate — nunca se actualiza
 * el stock directamente sin dejar rastro.
 */
class StockMovement extends Model
{
    use HasUuids, BelongsToBusiness;

    const UPDATED_AT = null;

    protected $fillable = [
        'business_id', 'product_id', 'type', 'quantity', 'stock_before', 'stock_after',
        'reference_sale_id', 'reference_purchase_id', 'reason', 'created_by',
    ];

    protected $casts = [
        'type' => StockMovementType::class,
        'quantity' => 'decimal:3',
        'stock_before' => 'decimal:3',
        'stock_after' => 'decimal:3',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class, 'reference_sale_id'); }
    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class, 'reference_purchase_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
