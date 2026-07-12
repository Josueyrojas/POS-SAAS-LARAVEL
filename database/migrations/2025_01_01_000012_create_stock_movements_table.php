<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('product_id');
            $table->string('type'); // StockMovementType: MANUAL_IN, MANUAL_OUT, SALE, SALE_VOID, PURCHASE, INITIAL, ADJUSTMENT
            $table->decimal('quantity', 12, 3); // con signo: + entra, - sale
            $table->decimal('stock_before', 12, 3);
            $table->decimal('stock_after', 12, 3);
            $table->uuid('reference_sale_id')->nullable();
            $table->uuid('reference_purchase_id')->nullable();
            $table->text('reason')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent(); // inmutable, igual que sale_items: sin updated_at

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('reference_sale_id')->references('id')->on('sales')->nullOnDelete();
            $table->foreign('reference_purchase_id')->references('id')->on('purchases')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['business_id', 'product_id', 'created_at']);
            $table->index(['business_id', 'type']);
        });
    }

    public function down(): void { Schema::dropIfExists('stock_movements'); }
};
