<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quién cambió qué y cuándo. No guardar datos sensibles (CLABE) en antes/despues.
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('tabla', 40);
            $table->unsignedBigInteger('registro_id');
            $table->enum('accion', ['crear', 'editar', 'eliminar']);
            $table->json('antes')->nullable();
            $table->json('despues')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tabla', 'registro_id'], 'ix_bitacora_registro');
            $table->foreign('usuario_id', 'fk_bitacora_usuario')
                ->references('id')->on('usuarios')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
