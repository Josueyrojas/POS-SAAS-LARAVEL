<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // (role = 'SUPER_ADMIN') = (business_id IS NULL): un super admin
        // nunca tiene negocio, y todo usuario de negocio siempre tiene uno.
        // Segunda capa de defensa a nivel de base de datos, además del
        // aislamiento que ya hace TenantContext/BusinessScope en la app.
        // ADD CONSTRAINT no existe en SQLite (solo se puede definir al crear
        // la tabla); se omite ahí — el motor de pruebas no necesita esta
        // capa extra, el código ya se prueba por su cuenta.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE users ADD CONSTRAINT chk_role_business
                 CHECK ((role = 'SUPER_ADMIN') = (business_id IS NULL))"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT chk_role_business');
        }
    }
};
