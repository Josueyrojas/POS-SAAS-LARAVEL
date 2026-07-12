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
        DB::statement(
            "ALTER TABLE users ADD CONSTRAINT chk_role_business
             CHECK ((role = 'SUPER_ADMIN') = (business_id IS NULL))"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT chk_role_business');
    }
};
