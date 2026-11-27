<?php

use App\Enums\AccionBitacora;
use App\Models\Adjunto;
use App\Models\Bitacora;
use App\Models\Gasto;
use App\Models\Usuario;
use Illuminate\Support\Facades\Storage;

it('SEG-08: invitado que intenta descargar un adjunto va al inicio de sesión', function () {
    $adjunto = Adjunto::factory()->create();

    $this->get(route('adjuntos.descargar', $adjunto))
        ->assertRedirect(route('login'));
});

it('SEG-08: usuario autenticado descarga el adjunto del padre que puede ver', function () {
    Storage::fake('local');

    $gasto = Gasto::factory()->create();
    $adjunto = Adjunto::factory()->create([
        'entidad' => 'gasto',
        'entidad_id' => $gasto->id,
    ]);
    Storage::disk('local')->put($adjunto->ruta, 'contenido de prueba');

    $this->actingAs(Usuario::factory()->create())
        ->get(route('adjuntos.descargar', $adjunto))
        ->assertOk();
});

it('SEG-08: devuelve 404 si el archivo ya no existe en disco', function () {
    Storage::fake('local'); // disco vacío

    $gasto = Gasto::factory()->create();
    $adjunto = Adjunto::factory()->create([
        'entidad' => 'gasto',
        'entidad_id' => $gasto->id,
    ]);

    $this->actingAs(Usuario::factory()->create())
        ->get(route('adjuntos.descargar', $adjunto))
        ->assertNotFound();
});

it('SEG-08: la ruta del adjunto usa nombre UUID, no el nombre original del archivo', function () {
    $adjunto = Adjunto::factory()->create(['nombre' => 'factura enero 2024.pdf']);

    // El nombre original se guarda tal cual; la ruta usa UUID
    expect($adjunto->nombre)->toBe('factura enero 2024.pdf')
        ->and($adjunto->ruta)->toMatch('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.(pdf|xml|jpg|jpeg|png)$/i');
});

it('RN-21: crear un adjunto deja entrada Crear en bitácora', function () {
    $adjunto = Adjunto::factory()->create();

    $bitacora = Bitacora::where('tabla', 'adjuntos')
        ->where('registro_id', $adjunto->id)
        ->where('accion', AccionBitacora::Crear)
        ->first();

    expect($bitacora)->not->toBeNull()
        ->and($bitacora->antes)->toBeNull()
        ->and($bitacora->despues)->not->toBeNull();
});

it('RN-21: eliminar un adjunto deja entrada Eliminar en bitácora', function () {
    Storage::fake('local');
    $adjunto = Adjunto::factory()->create();

    $adjunto->delete();

    $bitacora = Bitacora::where('tabla', 'adjuntos')
        ->where('registro_id', $adjunto->id)
        ->where('accion', AccionBitacora::Eliminar)
        ->first();

    expect($bitacora)->not->toBeNull()
        ->and($bitacora->despues)->toBeNull()
        ->and($bitacora->antes)->not->toBeNull();
});

it('SEG-08: devuelve 404 si la entidad no está en el morph map', function () {
    // 'factura' es un valor válido del ENUM pero no está registrado en enforceMorphMap todavía
    $adjunto = Adjunto::factory()->create(['entidad' => 'factura']);

    $this->actingAs(Usuario::factory()->create())
        ->get(route('adjuntos.descargar', $adjunto))
        ->assertNotFound();
});

it('SEG-08: devuelve 404 si el padre no existe', function () {
    $adjunto = Adjunto::factory()->create(['entidad' => 'gasto', 'entidad_id' => 999999]);

    $this->actingAs(Usuario::factory()->create())
        ->get(route('adjuntos.descargar', $adjunto))
        ->assertNotFound();
});

it('SEG-08: el archivo no es accesible desde public/storage', function () {
    $adjunto = Adjunto::factory()->create();

    // La ruta comienza con 'adjuntos/', nunca en public/
    expect($adjunto->ruta)->toStartWith('adjuntos/');

    // La ruta de disco no sirve el archivo sin autenticación (disco local con serve:true requiere permisos)
    expect($this->get('/storage/'.$adjunto->ruta)->status())->not->toBe(200);
});
