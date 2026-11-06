<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_por_pagar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id');
            $table->unsignedBigInteger('proyecto_id')->nullable();
            $table->date('fecha');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('concepto', 200)->nullable();
            $table->decimal('importe', 14, 2); // sin IVA
            $table->decimal('iva', 14, 2)->default(0.00);
            $table->enum('estatus', ['vigente', 'cancelada'])->default('vigente');
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            $table->index('proveedor_id', 'ix_cxp_proveedor');
            $table->index('proyecto_id', 'ix_cxp_proyecto');
            $table->foreign('proveedor_id', 'fk_cxp_proveedor')
                ->references('id')->on('proveedores')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('proyecto_id', 'fk_cxp_proyecto')
                ->references('id')->on('proyectos')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement('ALTER TABLE cuentas_por_pagar ADD CONSTRAINT ck_cxp_importe CHECK (importe >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_pagar');
    }
};
