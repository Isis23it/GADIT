<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Intereses y comisiones van como gasto con prestamo_id; el capital devuelto va en prestamo_pagos.
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('acreedor_id'); // persona que prestó
            $table->unsignedBigInteger('cuenta_id'); // cuenta donde se recibió el dinero
            $table->date('fecha');
            $table->decimal('monto', 14, 2);
            $table->decimal('tasa_pct', 5, 2)->default(0.00);
            $table->enum('tasa_periodo', ['mensual', 'anual', 'unica'])->default('mensual');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            $table->index('acreedor_id', 'ix_prestamos_acreedor');
            $table->index('cuenta_id', 'ix_prestamos_cuenta');
            $table->foreign('acreedor_id', 'fk_prestamos_acreedor')
                ->references('id')->on('personas')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('cuenta_id', 'fk_prestamos_cuenta')
                ->references('id')->on('cuentas')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement('ALTER TABLE prestamos ADD CONSTRAINT ck_prestamos_monto CHECK (monto > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
