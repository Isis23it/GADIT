<?php

use App\Livewire\Auth\MiPerfil;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('RF-85: si la confirmación no coincide, el campo se marca inválido y apunta al mensaje', function () {
    $html = Livewire::actingAs(Usuario::factory()->create())->test(MiPerfil::class)
        ->set('password', 'una-contrasena-larga-1')
        ->set('password_confirmation', 'otra-contrasena-distinta')
        ->call('cambiarContrasena')
        ->html();

    expect($html)->toMatch('/id="password_confirmation"[^>]*aria-invalid="true"[^>]*aria-describedby="password-error"/s');
});

it('RF-85: si la contraseña es corta pero la confirmación coincide, la confirmación no se marca', function () {
    $html = Livewire::actingAs(Usuario::factory()->create())->test(MiPerfil::class)
        ->set('password', 'corta')
        ->set('password_confirmation', 'corta')
        ->call('cambiarContrasena')
        ->html();

    expect($html)->toContain('id="password-error"')
        ->not->toMatch('/id="password_confirmation"[^>]*aria-invalid/s');
});

it('RF-85: sin errores la confirmación no lleva aria-invalid', function () {
    $html = Livewire::actingAs(Usuario::factory()->create())->test(MiPerfil::class)->html();

    expect($html)->not->toMatch('/id="password_confirmation"[^>]*aria-invalid/s');
});
