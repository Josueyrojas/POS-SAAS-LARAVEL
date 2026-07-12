<?php

namespace App\Models\Concerns;

use App\Models\Business;
use App\Models\Scopes\BusinessScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marca un modelo como perteneciente a un inquilino. Al usarlo:
 *  - toda consulta se filtra automáticamente por el business_id activo,
 *  - al crear un registro, se le asigna el business_id activo si no trae uno.
 * Es el equivalente Laravel del cliente con alcance `forBusiness(businessId)`:
 * el aislamiento deja de depender de que el desarrollador lo recuerde.
 */
trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope());

        static::creating(function ($model) {
            if (empty($model->business_id)) {
                $ctx = app(TenantContext::class);
                if ($ctx->has()) {
                    $model->business_id = $ctx->id();
                }
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
