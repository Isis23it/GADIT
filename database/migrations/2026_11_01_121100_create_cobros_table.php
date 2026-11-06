<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('factura_id');
            $table->unsignedBigInteger('cuenta_id'); // a qué cuenta entró el dinero
            $table->date('fecha');
            $table->decimal('importe', 14, 2); // sin IVA
            $table->decimal('iva', 14, 2)->default(0.00);
            $table->char('forma_pago', 2)->nullable(); // forma real del cobro (catálogo SAT)
            $table->string('referencia', 80)->nullable();
            $table->char('complemento_uuid', 36)->nullable(); // UUID del Complemento de Pago (no único)
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable(); // borrado lógico

            $table->index('factura_id', 'ix_cobros_factura');
            $table->index('complemento_uuid', 'ix_cobros_complemento');
            $table->index('cuenta_id', 'ix_cobros_cuenta');
            $table->index('fecha', 'ix_cobros_fecha');
            $table->foreign('factura_id', 'fk_cobros_factura')
                ->references('id')->on('facturas')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('cuenta_id', 'fk_cobros_cuenta')
                ->references('id')->on('cuentas')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE cobros
                ADD CONSTRAINT ck_cobros_importe CHECK (importe > 0),
                ADD CONSTRAINT ck_cobros_forma_pago CHECK (forma_pago IS NULL OR forma_pago REGEXP '^[0-9]{2}$'),
                ADD CONSTRAINT ck_cobros_complemento CHECK (complemento_uuid IS NULL OR complemento_uuid REGEXP '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$')
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('cobros');
    }
};
