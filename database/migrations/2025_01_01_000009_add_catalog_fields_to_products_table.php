<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->uuid('unit_of_measure_id')->nullable()->after('sku');
            $table->uuid('category_id')->nullable()->after('unit_of_measure_id');
            $table->decimal('wholesale_price', 12, 2)->nullable()->after('price');
            $table->decimal('wholesale_min_qty', 12, 3)->nullable()->after('wholesale_price');
            $table->decimal('cost_price', 12, 2)->nullable()->after('wholesale_min_qty');
            $table->decimal('stock_minimo', 12, 3)->default(0)->after('stock');

            $table->foreign('unit_of_measure_id')->references('id')->on('units_of_measure')->restrictOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->index(['business_id', 'category_id']);
            $table->index(['business_id', 'unit_of_measure_id']);
        });

        // Backfill: todo producto existente queda en "Pieza" antes de exigir NOT NULL.
        $piezaId = DB::table('units_of_measure')->where('name', 'Pieza')->value('id');
        DB::table('products')->whereNull('unit_of_measure_id')->update(['unit_of_measure_id' => $piezaId]);

        // price -> retail_price (es el precio de menudeo). RENAME COLUMN es
        // sintaxis ANSI soportada nativamente por Postgres y SQLite (desde
        // 3.25), sin doctrine/dbal.
        DB::statement('ALTER TABLE products RENAME COLUMN price TO retail_price');

        // SET NOT NULL y el cambio de tipo de `stock` (integer -> decimal)
        // usan ALTER COLUMN, específico de Postgres — SQLite no lo soporta
        // (y como es de tipado dinámico, ya acepta decimales sin esto). Se
        // omite en pruebas; en Postgres real sí se aplica.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products ALTER COLUMN unit_of_measure_id SET NOT NULL');
            DB::statement('ALTER TABLE products ALTER COLUMN stock TYPE numeric(12,3) USING stock::numeric');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products ALTER COLUMN stock TYPE integer USING round(stock)::integer');
        }
        DB::statement('ALTER TABLE products RENAME COLUMN retail_price TO price');

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['unit_of_measure_id']);
            $table->dropForeign(['category_id']);
            $table->dropIndex(['business_id', 'category_id']);
            $table->dropIndex(['business_id', 'unit_of_measure_id']);
            $table->dropColumn([
                'unit_of_measure_id', 'category_id',
                'wholesale_price', 'wholesale_min_qty', 'cost_price', 'stock_minimo',
            ]);
        });
    }
};
