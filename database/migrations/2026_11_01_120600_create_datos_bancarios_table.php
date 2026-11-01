<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cuentas bancarias de terceros (proveedores y personas). Dato financiero sensible (SEG-07):
        //  - la app guarda la CLABE CIFRADA (por eso VARCHAR(255)) y valida su dígito verificador al capturar;
        //  - clabe_ultimos4 permite mostrarla enmascarada sin descifrar;
        //  - una sola cuenta 'principal' por tercero es regla de la app.
        Schema::create('datos_bancarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->unsignedBigInteger('persona_id')->nullable();
            $table->string('banco', 50);
            $table->string('clabe', 255);
            $table->char('clabe_ultimos4', 4);
            $table->string('titular', 120)->nullable(); // a quien está la cuenta, si difiere del proveedor/persona
            $table->boolean('principal')->default(false);
            $table->boolean('activa')->default(true);
            $table->string('notas', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('proveedor_id', 'ix_bancarios_proveedor');
            $table->index('persona_id', 'ix_bancarios_persona');
            $table->foreign('proveedor_id', 'fk_bancarios_proveedor')
                ->references('id')->on('proveedores')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('persona_id', 'fk_bancarios_persona')
                ->references('id')->on('personas')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE datos_bancarios
                ADD CONSTRAINT ck_bancarios_duenio CHECK ((proveedor_id IS NULL) <> (persona_id IS NULL)),
                ADD CONSTRAINT ck_bancarios_ultimos4 CHECK (clabe_ultimos4 REGEXP '^[0-9]{4}$')
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('datos_bancarios');
    }
};
