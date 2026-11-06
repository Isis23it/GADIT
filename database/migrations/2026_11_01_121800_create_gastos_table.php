<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // semana_iso la calcula MySQL con YEARWEEK(fecha, 3) (RN-11) y reemplaza la columna SEM del Excel.
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->integer('semana_iso')->storedAs('YEARWEEK(fecha, 3)');
            $table->string('concepto', 200);
            $table->decimal('importe_con_iva', 14, 2);
            $table->decimal('iva_incluido', 14, 2)->nullable(); // IVA acreditable; NULL en datos históricos
            $table->char('cfdi_uuid', 36)->nullable(); // no único: un CFDI puede respaldar varios pagos
            $table->unsignedBigInteger('categoria_id');
            $table->unsignedBigInteger('cuenta_id'); // de dónde salió el dinero
            $table->unsignedBigInteger('proyecto_id')->nullable(); // NULL = gasto general de la empresa
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->unsignedBigInteger('beneficiario_id')->nullable(); // persona: comisión, utilidad, apoyo, viático
            $table->unsignedBigInteger('cuenta_por_pagar_id')->nullable(); // pago de una compra a crédito
            $table->unsignedBigInteger('prestamo_id')->nullable(); // intereses y comisiones de un préstamo
            $table->unsignedBigInteger('traspaso_id')->nullable(); // comisión generada por un traspaso
            $table->date('periodo_inicio')->nullable();
            $table->date('periodo_fin')->nullable();
            $table->enum('estatus_revision', ['sin_revisar', 'revisado', 'falta_comprobante'])->default('sin_revisar');
            $table->string('observaciones', 255)->nullable();
            $table->string('origen', 20)->nullable(); // 'GADIT' o 'GADIT 2': trazabilidad de la importación
            $table->unsignedInteger('numero_origen')->nullable(); // columna No del Excel
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            $table->unique(['origen', 'numero_origen'], 'uq_gastos_origen');
            $table->index('fecha', 'ix_gastos_fecha');
            $table->index('semana_iso', 'ix_gastos_semana');
            $table->index('categoria_id', 'ix_gastos_categoria');
            $table->index('cuenta_id', 'ix_gastos_cuenta');
            $table->index('proyecto_id', 'ix_gastos_proyecto');
            $table->index('proveedor_id', 'ix_gastos_proveedor');
            $table->index('beneficiario_id', 'ix_gastos_beneficiario');
            $table->index('cuenta_por_pagar_id', 'ix_gastos_cxp');
            $table->index('prestamo_id', 'ix_gastos_prestamo');
            $table->index('traspaso_id', 'ix_gastos_traspaso');
            $table->index('cfdi_uuid', 'ix_gastos_cfdi');
            $table->foreign('categoria_id', 'fk_gastos_categoria')
                ->references('id')->on('categorias')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('cuenta_id', 'fk_gastos_cuenta')
                ->references('id')->on('cuentas')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('proyecto_id', 'fk_gastos_proyecto')
                ->references('id')->on('proyectos')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('proveedor_id', 'fk_gastos_proveedor')
                ->references('id')->on('proveedores')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('beneficiario_id', 'fk_gastos_beneficiario')
                ->references('id')->on('personas')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('cuenta_por_pagar_id', 'fk_gastos_cxp')
                ->references('id')->on('cuentas_por_pagar')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('prestamo_id', 'fk_gastos_prestamo')
                ->references('id')->on('prestamos')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('traspaso_id', 'fk_gastos_traspaso')
                ->references('id')->on('traspasos')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('creado_por', 'fk_gastos_creado_por')
                ->references('id')->on('usuarios')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE gastos
                ADD CONSTRAINT ck_gastos_importe CHECK (importe_con_iva <> 0),
                ADD CONSTRAINT ck_gastos_periodo CHECK (periodo_inicio IS NULL OR periodo_fin IS NULL OR periodo_fin >= periodo_inicio),
                ADD CONSTRAINT ck_gastos_cfdi CHECK (cfdi_uuid IS NULL OR cfdi_uuid REGEXP '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$')
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
