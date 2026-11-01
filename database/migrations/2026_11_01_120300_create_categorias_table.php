<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reemplaza las 153 etiquetas libres de GADIT. 'grupo' permite reportar y separar el reparto de utilidad.
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->enum('grupo', [
                'obra', 'nomina', 'impuestos', 'comisiones', 'reparto_utilidad',
                'financiamiento', 'logistica', 'administracion', 'otros',
            ]);
            $table->boolean('activa')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('nombre', 'uq_categorias_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
