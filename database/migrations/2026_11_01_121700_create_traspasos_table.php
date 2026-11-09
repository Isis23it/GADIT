<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La comisión del intermediario se registra como gasto con traspaso_id.
        Schema::create('traspasos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->unsignedBigInteger('cuenta_origen_id');
            $table->unsignedBigInteger('cuenta_destino_id');
            $table->decimal('monto', 14, 2);
            $table->decimal('comision_pct', 5, 2)->nullable(); // informativo
            $table->string('concepto', 200)->nullable();
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            $table->index('fecha', 'ix_traspasos_fecha');
            $table->index('cuenta_origen_id', 'ix_traspasos_origen');
            $table->index('cuenta_destino_id', 'ix_traspasos_destino');
            $table->foreign('cuenta_origen_id', 'fk_traspasos_origen')
                ->references('id')->on('cuentas')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('cuenta_destino_id', 'fk_traspasos_destino')
                ->references('id')->on('cuentas')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE traspasos
                ADD CONSTRAINT ck_traspasos_monto CHECK (monto > 0),
                ADD CONSTRAINT ck_traspasos_distintas CHECK (cuenta_origen_id <> cuenta_destino_id)
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('traspasos');
    }
};
