<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id'); // redundante a propósito (defensa en profundidad)
            $table->uuid('sale_id');
            $table->uuid('product_id')->nullable();
            $table->string('name_snapshot');   // congelado al momento de la venta
            $table->decimal('unit_price', 12, 2);
            $table->integer('quantity');
            $table->decimal('line_total', 12, 2);

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->index('business_id');
            $table->index('sale_id');
        });
    }

    public function down(): void { Schema::dropIfExists('sale_items'); }
};
