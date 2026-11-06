<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La obra. Aquí se pega el costo: gastos.proyecto_id es lo que el Excel no tenía.
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->string('codigo', 30)->nullable();
            $table->string('nombre', 160);
            $table->string('ubicacion', 160)->nullable();
            $table->enum('estatus', ['en_proceso', 'terminado', 'suspendido'])->default('en_proceso');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('codigo', 'uq_proyectos_codigo');
            $table->index('cliente_id', 'ix_proyectos_cliente');
            $table->foreign('cliente_id', 'fk_proyectos_cliente')
                ->references('id')->on('clientes')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
