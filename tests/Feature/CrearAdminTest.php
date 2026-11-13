<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
});

it('E-18: gadit:crear-admin crea el administrador con datos válidos', function () {
    $this->artisan('gadit:crear-admin')
        ->expectsQuestion('Nombre', 'Angel Admin')
        ->expectsQuestion('Correo electrónico', 'angel@gadit.com')
        ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'ClaveSegura123!')
        ->expectsQuestion('Confirmar contraseña', 'ClaveSegura123!')
        ->assertExitCode(0);

    expect(Usuario::where('email', 'angel@gadit.com')->exists())->toBeTrue();
});

it('E-18: gadit:crear-admin rechaza un correo ya registrado', function () {
    Usuario::factory()->create(['email' => 'existe@gadit.com']);

    $this->artisan('gadit:crear-admin')
        ->expectsQuestion('Nombre', 'Angel Admin')
        ->expectsQuestion('Correo electrónico', 'existe@gadit.com')
        ->assertExitCode(1);
});

it('SEG-01: gadit:crear-admin rechaza una contraseña de menos de 12 caracteres', function () {
    $this->artisan('gadit:crear-admin')
        ->expectsQuestion('Nombre', 'Angel Admin')
        ->expectsQuestion('Correo electrónico', 'angel@gadit.com')
        ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'corta')
        ->assertExitCode(1);

    expect(Usuario::where('email', 'angel@gadit.com')->exists())->toBeFalse();
});
