<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Comprobantes de cada registro; el estatus de revisión vive en gastos.
        Schema::create('adjuntos', function (Blueprint $table) {
            $table->id();
            $table->enum('entidad', ['gasto', 'factura', 'cobro', 'contrato', 'cuenta_por_pagar', 'prestamo']);
            $table->unsignedBigInteger('entidad_id');
            $table->string('nombre', 180);
            $table->string('ruta', 255);
            $table->string('mime', 80)->nullable();
            $table->unsignedBigInteger('subido_por')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entidad', 'entidad_id'], 'ix_adjuntos_entidad');
            $table->foreign('subido_por', 'fk_adjuntos_usuario')
                ->references('id')->on('usuarios')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjuntos');
    }
};
