<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "No ejecutado", deductivas y descuentos que reducen lo que se puede facturar.
        // Importes en positivo = lo que se resta del contrato.
        Schema::create('contrato_ajustes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id');
            $table->enum('tipo', ['no_ejecutado', 'deductiva', 'descuento', 'otro']);
            $table->string('concepto', 200)->nullable();
            $table->decimal('importe', 14, 2);
            $table->date('fecha')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('contrato_id', 'ix_ajustes_contrato');
            $table->foreign('contrato_id', 'fk_ajustes_contrato')
                ->references('id')->on('contratos')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_ajustes');
    }
};
