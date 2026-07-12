<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('branch_id')->nullable();
            $table->uuid('seller_id')->nullable();
            $table->string('status')->default('COMPLETED');
            $table->decimal('total', 12, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('seller_id')->references('id')->on('users')->nullOnDelete();
            $table->index('business_id');
            $table->index(['business_id', 'created_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('sales'); }
};
