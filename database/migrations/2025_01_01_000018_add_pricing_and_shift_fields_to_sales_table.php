<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->uuid('cash_session_id')->nullable()->after('customer_id');
            $table->decimal('items_subtotal', 12, 2)->nullable()->after('total');
            $table->string('discount_type')->nullable()->after('items_subtotal'); // PERCENT, FIXED
            $table->decimal('discount_value', 12, 3)->nullable()->after('discount_type');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_value');
            // Snapshot de la tasa del negocio al momento de la venta (mismo
            // patrón defensivo que name_snapshot en sale_items): un cambio
            // futuro de tax_rate no debe reinterpretar ventas históricas.
            $table->decimal('tax_rate', 5, 2)->nullable()->after('discount_amount');
            $table->decimal('tax_amount', 12, 2)->nullable()->after('tax_rate');

            $table->foreign('cash_session_id')->references('id')->on('cash_sessions')->nullOnDelete();
            $table->index('cash_session_id');
        });

        // Backfill de ventas existentes (de las Fases 1-3, antes de que
        // existieran estos campos): items_subtotal = total, sin descuento,
        // sin desglose de IVA (se desconoce la tasa vigente en ese momento).
        DB::table('sales')->whereNull('items_subtotal')->update([
            'items_subtotal' => DB::raw('total'),
        ]);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['cash_session_id']);
            $table->dropIndex(['cash_session_id']);
            $table->dropColumn([
                'cash_session_id', 'items_subtotal', 'discount_type',
                'discount_value', 'discount_amount', 'tax_rate', 'tax_amount',
            ]);
        });
    }
};
