<?php

namespace App\Support;

/**
 * Guarda el business_id del inquilino activo durante el ciclo de una petición.
 * Lo fija el middleware `business` a partir del usuario autenticado. Mientras
 * esté fijado, el BusinessScope filtra todas las consultas de los modelos de
 * inquilino. Si NO está fijado (plataforma / público / seeding), el scope no
 * hace nada -> consultas globales (que es justo lo que necesita el Super Admin).
 */
class TenantContext
{
    protected ?string $businessId = null;

    public function set(?string $businessId): void
    {
        $this->businessId = $businessId;
    }

    public function id(): ?string
    {
        return $this->businessId;
    }

    public function has(): bool
    {
        return $this->businessId !== null;
    }

    /** Ejecuta un callback ignorando temporalmente el inquilino activo. */
    public function withoutScope(callable $callback): mixed
    {
        $previous = $this->businessId;
        $this->businessId = null;
        try {
            return $callback();
        } finally {
            $this->businessId = $previous;
        }
    }
}
