<?php

use App\Livewire\Auth\MiPerfil;
use App\Models\Usuario;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    app()->setLocale('es');
});

it('SEG-01: la contraseña corta se rechaza con un mensaje en español', function () {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
    $usuario = Usuario::factory()->create();

    Livewire::actingAs($usuario)->test(MiPerfil::class)
        ->set('password_actual', 'password')
        ->set('password', 'corta')
        ->set('password_confirmation', 'corta')
        ->call('cambiarContrasena')
        ->assertHasErrors(['password'])
        ->assertSee('al menos 12 caracteres');
});

it('SEG-01: la contraseña filtrada se rechaza con un mensaje en español', function () {
    $clave = 'una-clave-larga-y-unica-2026';
    $hash = strtoupper(sha1($clave));
    Http::fake(['api.pwnedpasswords.com/*' => Http::response(substr($hash, 5).':3', 200)]);
    $usuario = Usuario::factory()->create();

    Livewire::actingAs($usuario)->test(MiPerfil::class)
        ->set('password_actual', 'password')
        ->set('password', $clave)
        ->set('password_confirmation', $clave)
        ->call('cambiarContrasena')
        ->assertHasErrors(['password'])
        ->assertSee('apareció en una filtración');
});
