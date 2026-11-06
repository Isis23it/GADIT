<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El saldo sale de los movimientos (v_saldo_cuentas): ya no se teclea dos veces.
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->enum('tipo', ['banco', 'caja', 'intermediario']);
            $table->unsignedBigInteger('persona_id')->nullable(); // quién la custodia
            $table->boolean('activa')->default(true);
            $table->decimal('saldo_inicial', 14, 2)->default(0.00); // saldo contado el día del arranque
            $table->date('fecha_saldo_inicial')->nullable(); // solo los movimientos POSTERIORES a esta fecha se suman
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('nombre', 'uq_cuentas_nombre');
            $table->foreign('persona_id', 'fk_cuentas_persona')
                ->references('id')->on('personas')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
