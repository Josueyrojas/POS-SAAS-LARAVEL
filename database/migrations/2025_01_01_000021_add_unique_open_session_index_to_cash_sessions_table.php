<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Índice único parcial: garantiza a nivel de base de datos que un
        // usuario no puede tener dos turnos OPEN a la vez, sin importar
        // condiciones de carrera (doble clic, reintento de red) en el
        // controller. Sintaxis soportada tanto por Postgres como por SQLite.
        DB::statement(
            'CREATE UNIQUE INDEX cash_sessions_one_open_per_user
             ON cash_sessions (user_id) WHERE status = \'OPEN\''
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX cash_sessions_one_open_per_user');
    }
};
