<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BusinessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $ctx = app(TenantContext::class);

        // Solo filtramos cuando hay un inquilino activo. Sin contexto (Super
        // Admin, seeding) las consultas quedan globales, a propósito.
        if ($ctx->has()) {
            $builder->where($model->getTable() . '.business_id', $ctx->id());
        }
    }
}
