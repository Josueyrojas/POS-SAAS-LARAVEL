<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('address')->nullable()->after('tax_rate');
            $table->string('phone')->nullable()->after('address');
            $table->string('logo_url')->nullable()->after('phone');
            $table->text('receipt_footer')->nullable()->after('logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['address', 'phone', 'logo_url', 'receipt_footer']);
        });
    }
};
