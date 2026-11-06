<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Subtotal, IVA, total y saldo no se guardan: los calcula v_facturas (una sola fuente de verdad).
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id');
            $table->string('folio', 30);
            $table->char('uuid', 36)->nullable(); // Folio Fiscal (UUID) del CFDI; NULL en las históricas
            $table->enum('metodo_pago', ['PUE', 'PPD'])->nullable(); // PPD = parcialidades/diferido
            $table->char('forma_pago', 2)->nullable(); // catálogo SAT c_FormaPago
            $table->enum('tipo', ['anticipo', 'estimacion', 'finiquito', 'otro'])->default('estimacion');
            $table->unsignedTinyInteger('numero_estimacion')->nullable();
            $table->string('concepto', 200);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('importe', 14, 2); // base, antes de retención e IVA
            $table->decimal('retencion_pct', 5, 2)->default(0.00);
            $table->decimal('iva_pct', 5, 2)->default(16.00);
            $table->enum('estatus', ['emitida', 'cancelada'])->default('emitida');
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('folio', 'uq_facturas_folio');
            $table->unique('uuid', 'uq_facturas_uuid');
            $table->index('contrato_id', 'ix_facturas_contrato');
            $table->index('fecha_emision', 'ix_facturas_emision');
            $table->foreign('contrato_id', 'fk_facturas_contrato')
                ->references('id')->on('contratos')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE facturas
                ADD CONSTRAINT ck_facturas_importe CHECK (importe >= 0),
                ADD CONSTRAINT ck_facturas_retencion CHECK (retencion_pct BETWEEN 0 AND 100),
                ADD CONSTRAINT ck_facturas_uuid CHECK (uuid IS NULL OR uuid REGEXP '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$'),
                ADD CONSTRAINT ck_facturas_forma_pago CHECK (forma_pago IS NULL OR forma_pago REGEXP '^[0-9]{2}$')
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
