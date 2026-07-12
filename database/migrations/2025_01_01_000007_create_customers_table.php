<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('document_id')->nullable(); // RFC/NIT/RUC genérico, no se asume país
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            // NULLs no colisionan: muchos clientes pueden no tener documento.
            $table->unique(['business_id', 'document_id']);
            $table->index('business_id');
        });
    }

    public function down(): void { Schema::dropIfExists('customers'); }
};
