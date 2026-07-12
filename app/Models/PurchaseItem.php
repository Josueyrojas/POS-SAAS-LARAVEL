<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de compra. Lleva business_id explícito (mismo patrón de defensa en
 * profundidad que SaleItem) y congela name_snapshot al momento de la compra.
 */
class PurchaseItem extends Model
{
    use HasUuids, BelongsToBusiness;

    public $timestamps = false;

    protected $fillable = [
        'business_id', 'purchase_id', 'product_id', 'name_snapshot', 'unit_cost', 'quantity', 'line_total',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'quantity' => 'decimal:3',
        'line_total' => 'decimal:2',
    ];

    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
