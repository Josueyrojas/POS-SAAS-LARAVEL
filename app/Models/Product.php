<?php

namespace App\Models;

use App\Enums\PriceType;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'name', 'sku', 'unit_of_measure_id', 'category_id',
        'retail_price', 'wholesale_price', 'wholesale_min_qty', 'cost_price',
        'stock', 'stock_minimo', 'is_active', 'business_id',
    ];

    protected $casts = [
        'retail_price' => 'decimal:2', // dinero: nunca float
        'wholesale_price' => 'decimal:2',
        'wholesale_min_qty' => 'decimal:3',
        'cost_price' => 'decimal:2',
        'stock' => 'decimal:3',
        'stock_minimo' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function unitOfMeasure(): BelongsTo { return $this->belongsTo(UnitOfMeasure::class); }

    /** Precio y tipo de tarifa aplicables para una cantidad dada (regla mayoreo/menudeo). */
    public function priceFor(float $quantity): array
    {
        if ($this->wholesale_price !== null && $this->wholesale_min_qty !== null
            && $quantity >= (float) $this->wholesale_min_qty) {
            return [$this->wholesale_price, PriceType::WHOLESALE];
        }

        return [$this->retail_price, PriceType::RETAIL];
    }

    public function isLowStock(): bool
    {
        return (float) $this->stock <= (float) $this->stock_minimo;
    }
}
