<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('price_type')->default('RETAIL')->after('unit_price');
            $table->string('unit_label_snapshot')->nullable()->after('name_snapshot');
        });

        // quantity: integer -> decimal(12,3), soporta cantidades fraccionarias (ej. 3.5 metros).
        DB::statement('ALTER TABLE sale_items ALTER COLUMN quantity TYPE numeric(12,3) USING quantity::numeric');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sale_items ALTER COLUMN quantity TYPE integer USING round(quantity)::integer');

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['price_type', 'unit_label_snapshot']);
        });
    }
};
