<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo GLOBAL (sin business_id): las unidades son universales,
        // no un dato propio de cada negocio. Se siembra aquí, no solo en el
        // seeder, para que exista en cualquier entorno tras `migrate`.
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('abbreviation');
            $table->boolean('allows_decimal')->default(true);
            $table->timestamps();
        });

        $now = now();
        $units = [
            ['name' => 'Pieza', 'abbreviation' => 'pza', 'allows_decimal' => false],
            ['name' => 'Metro', 'abbreviation' => 'm', 'allows_decimal' => true],
            ['name' => 'Kilogramo', 'abbreviation' => 'kg', 'allows_decimal' => true],
            ['name' => 'Litro', 'abbreviation' => 'L', 'allows_decimal' => true],
            ['name' => 'Paquete', 'abbreviation' => 'paq', 'allows_decimal' => false],
            ['name' => 'Caja', 'abbreviation' => 'caja', 'allows_decimal' => false],
            ['name' => 'Rollo', 'abbreviation' => 'rollo', 'allows_decimal' => true],
            ['name' => 'Par', 'abbreviation' => 'par', 'allows_decimal' => false],
        ];

        foreach ($units as $unit) {
            DB::table('units_of_measure')->insert([
                'id' => (string) Str::uuid(),
                'name' => $unit['name'],
                'abbreviation' => $unit['abbreviation'],
                'allows_decimal' => $unit['allows_decimal'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void { Schema::dropIfExists('units_of_measure'); }
};
