<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('supplier_id');
            $table->uuid('branch_id')->nullable();
            $table->string('status')->default('PENDING');
            $table->string('invoice_number')->nullable();
            $table->date('purchase_date');
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('business_id');
            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('purchases'); }
};
