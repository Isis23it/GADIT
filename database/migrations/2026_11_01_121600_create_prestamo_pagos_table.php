<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamo_pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prestamo_id');
            $table->unsignedBigInteger('cuenta_id'); // de dónde salió el pago
            $table->date('fecha');
            $table->decimal('monto_capital', 14, 2);
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            $table->index('prestamo_id', 'ix_pagos_prestamo');
            $table->index('cuenta_id', 'ix_pagos_cuenta');
            $table->foreign('prestamo_id', 'fk_pagos_prestamo')
                ->references('id')->on('prestamos')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('cuenta_id', 'fk_pagos_cuenta')
                ->references('id')->on('cuentas')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement('ALTER TABLE prestamo_pagos ADD CONSTRAINT ck_pagos_monto CHECK (monto_capital > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamo_pagos');
    }
};
