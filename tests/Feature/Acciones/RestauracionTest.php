<?php

use App\Enums\AccionBitacora;
use App\Models\Bitacora;
use App\Models\Gasto;

it('RN-21: restauración registra entrada Editar con deleted_at: null en bitácora', function () {
    $gasto = Gasto::factory()->create();
    $gasto->delete();

    $gasto->restore();

    $bitacora = Bitacora::where('tabla', 'gastos')
        ->where('registro_id', $gasto->id)
        ->where('accion', AccionBitacora::Editar)
        ->orderByDesc('id')
        ->first();

    expect($bitacora)->not->toBeNull()
        ->and($bitacora->despues)->toHaveKey('deleted_at')
        ->and($bitacora->despues['deleted_at'])->toBeNull();
});

it('RN-21: restauración reactiva el registro en base de datos', function () {
    $gasto = Gasto::factory()->create();
    $gasto->delete();

    $gasto->restore();

    expect(Gasto::find($gasto->id))->not->toBeNull();
});
