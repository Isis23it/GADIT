<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Socios, custodios de efectivo, prestamistas, beneficiarios de comisiones... (un solo catálogo).
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('alias', 60)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->boolean('es_socio')->default(false);
            $table->boolean('activo')->default(true);
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('nombre', 'uq_personas_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
