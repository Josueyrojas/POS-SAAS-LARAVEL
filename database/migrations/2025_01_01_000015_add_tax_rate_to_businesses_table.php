<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // 16% (IVA general en México) por default: un negocio nuevo
            // queda funcional sin que nadie tenga que configurar nada.
            $table->decimal('tax_rate', 5, 2)->default(16.00)->after('plan');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('tax_rate');
        });
    }
};
