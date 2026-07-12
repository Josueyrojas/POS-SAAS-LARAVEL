<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bug latente: la migración original de `sales` solo define created_at,
        // pero el modelo Sale no tiene $timestamps=false. El primer Sale::create()
        // habría fallado con "column updated_at does not exist". Se corrige aquí,
        // junto con los campos de auditoría que de todos modos la necesitan.
        DB::statement('ALTER TABLE sales ADD COLUMN updated_at timestamp NULL');
        DB::statement('UPDATE sales SET updated_at = created_at WHERE updated_at IS NULL');

        Schema::table('sales', function (Blueprint $table) {
            $table->uuid('customer_id')->nullable()->after('branch_id');
            $table->timestamp('voided_at')->nullable();
            $table->uuid('voided_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->uuid('refunded_by')->nullable();
            $table->text('refund_reason')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('amount_tendered', 12, 2)->nullable();
            $table->decimal('change_due', 12, 2)->nullable();

            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('voided_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('refunded_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['business_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['voided_by']);
            $table->dropForeign(['refunded_by']);
            $table->dropIndex(['business_id', 'customer_id']);
            $table->dropColumn([
                'customer_id', 'voided_at', 'voided_by', 'void_reason',
                'refunded_at', 'refunded_by', 'refund_reason',
                'payment_method', 'amount_tendered', 'change_due',
            ]);
        });

        DB::statement('ALTER TABLE sales DROP COLUMN updated_at');
    }
};
