<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Los porcentajes (30/25/22.5/22.5, 65/20/15...) viven aquí, no en celdas.
        // Regla de aplicación: los porcentajes de un esquema deben sumar 100.
        Schema::create('esquemas_reparto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->unsignedBigInteger('proyecto_id')->nullable(); // NULL = aplica a toda la empresa
            $table->boolean('vigente')->default(true);
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('proyecto_id', 'ix_esquemas_proyecto');
            $table->foreign('proyecto_id', 'fk_esquemas_proyecto')
                ->references('id')->on('proyectos')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esquemas_reparto');
    }
};
