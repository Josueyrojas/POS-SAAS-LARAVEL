<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo GLOBAL de unidades de medida (pieza, metro, kg...). No usa
 * BelongsToBusiness a propósito: la unidad es universal, no un dato de negocio.
 */
class UnitOfMeasure extends Model
{
    use HasUuids;

    protected $table = 'units_of_measure';

    protected $fillable = ['name', 'abbreviation', 'allows_decimal'];

    protected $casts = [
        'allows_decimal' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
