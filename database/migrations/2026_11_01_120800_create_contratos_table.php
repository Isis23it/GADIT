<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pedido / contrato. Una adenda es otro contrato con tipo='adenda' y contrato_padre_id.
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyecto_id');
            $table->unsignedBigInteger('contrato_padre_id')->nullable();
            $table->enum('tipo', ['contrato', 'adenda'])->default('contrato');
            $table->string('numero_pedido', 60)->nullable();
            $table->string('concepto', 200);
            $table->decimal('importe', 14, 2); // sin IVA
            $table->decimal('iva_pct', 5, 2)->default(16.00);
            $table->date('fecha')->nullable();
            $table->enum('estatus', ['vigente', 'terminado', 'cancelado'])->default('vigente');
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('proyecto_id', 'ix_contratos_proyecto');
            $table->index('contrato_padre_id', 'ix_contratos_padre');
            $table->index('numero_pedido', 'ix_contratos_pedido');
            $table->foreign('proyecto_id', 'fk_contratos_proyecto')
                ->references('id')->on('proyectos')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('contrato_padre_id', 'fk_contratos_padre')
                ->references('id')->on('contratos')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement('ALTER TABLE contratos ADD CONSTRAINT ck_contratos_importe CHECK (importe >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
