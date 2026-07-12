<?php

namespace App\Models;

use App\Enums\PriceType;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de venta. Lleva business_id explícito (defensa en profundidad) y congela
 * nombre, unidad y precio unitario al momento de la venta, para que un cambio
 * posterior en el producto no altere el histórico contable.
 */
class SaleItem extends Model
{
    use HasUuids, BelongsToBusiness;

    // La tabla sale_items no tiene columnas created_at/updated_at (es un
    // registro inmutable, como purchase_items): sin esto, el primer
    // SaleItem::create() fallaría intentando insertar timestamps inexistentes.
    public $timestamps = false;

    protected $fillable = [
        'business_id', 'sale_id', 'product_id',
        'name_snapshot', 'unit_label_snapshot', 'unit_price', 'price_type', 'quantity', 'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'price_type' => PriceType::class,
        'quantity' => 'decimal:3',
        'line_total' => 'decimal:2',
    ];

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
