<?php

namespace App\Models;

use App\Enums\BusinessPlan;
use App\Enums\BusinessStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Raíz del inquilino. NO usa BelongsToBusiness: se identifica por `id`, no por
 * `business_id`. El Super Admin consulta esta tabla globalmente.
 */
class Business extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'slug', 'status', 'plan'];

    protected $casts = [
        'status' => BusinessStatus::class,
        'plan' => BusinessPlan::class,
    ];

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function branches(): HasMany { return $this->hasMany(Branch::class); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }
}
