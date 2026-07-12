<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id'); // redundante a propósito (mismo patrón que sale_items)
            $table->uuid('purchase_id');
            $table->uuid('product_id')->nullable();
            $table->string('name_snapshot');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('quantity', 12, 3);
            $table->decimal('line_total', 12, 2);

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('purchase_id')->references('id')->on('purchases')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->index('business_id');
            $table->index('purchase_id');
        });
    }

    public function down(): void { Schema::dropIfExists('purchase_items'); }
};
