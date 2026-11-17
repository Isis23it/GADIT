<?php

use App\Enums\AccionBitacora;
use App\Models\Bitacora;
use App\Models\Gasto;
use Illuminate\Support\Facades\DB;

it('RN-20: borrado lógico registra entrada Eliminar en bitácora', function () {
    $gasto = Gasto::factory()->create();

    $gasto->delete();

    $bitacora = Bitacora::where('tabla', 'gastos')
        ->where('registro_id', $gasto->id)
        ->where('accion', AccionBitacora::Eliminar)
        ->first();

    expect($bitacora)->not->toBeNull()
        ->and($bitacora->despues)->toBeNull()
        ->and($bitacora->antes)->not->toBeNull();
});

it('RN-20: borrado lógico no elimina el registro físicamente', function () {
    $gasto = Gasto::factory()->create();

    $gasto->delete();

    expect(DB::table('gastos')->where('id', $gasto->id)->whereNotNull('deleted_at')->exists())
        ->toBeTrue();
});
