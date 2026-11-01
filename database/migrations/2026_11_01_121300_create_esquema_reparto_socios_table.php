<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esquema_reparto_socios', function (Blueprint $table) {
            $table->unsignedBigInteger('esquema_id');
            $table->unsignedBigInteger('persona_id');
            $table->decimal('porcentaje', 5, 2);

            $table->primary(['esquema_id', 'persona_id']);
            $table->foreign('esquema_id', 'fk_esq_socios_esquema')
                ->references('id')->on('esquemas_reparto')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('persona_id', 'fk_esq_socios_persona')
                ->references('id')->on('personas')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement('ALTER TABLE esquema_reparto_socios ADD CONSTRAINT ck_esq_socios_pct CHECK (porcentaje > 0 AND porcentaje <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('esquema_reparto_socios');
    }
};
