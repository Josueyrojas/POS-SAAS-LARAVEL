<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No se reutiliza la `password_reset_tokens` del scaffold: su PK es
        // `email`, y aquí el email NO es único globalmente (solo por
        // negocio) — dos negocios distintos pueden compartir un usuario con
        // el mismo correo, y esa tabla los haría chocar. Esta se indexa por
        // user_id en su lugar.
        Schema::create('password_resets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('token_hash')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('user_id');
        });
    }

    public function down(): void { Schema::dropIfExists('password_resets'); }
};
