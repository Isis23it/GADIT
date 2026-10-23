<?php

use App\Livewire\Auth\IniciarSesion;
use App\Livewire\Auth\OlvideMiContrasena;
use App\Models\Usuario;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    Carbon\Carbon::setTestNow('2026-03-16 10:00', 'America/Mexico_City');
    RateLimiter::clear('login_email:test@example.com');
    RateLimiter::clear('login_ip:127.0.0.1');
});

it('RF-11: muestra la pantalla de inicio de sesión', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSeeLivewire(IniciarSesion::class);
});

it('RF-11: redirige al tablero tras credenciales correctas', function () {
    $usuario = Usuario::factory()->create();

    Livewire::test(IniciarSesion::class)
        ->set('email', $usuario->email)
        ->set('password', 'password')
        ->call('iniciar')
        ->assertRedirect(route('tablero'));

    $this->assertAuthenticatedAs($usuario);
});

it('RF-11: credenciales incorrectas muestran mensaje genérico', function () {
    Usuario::factory()->create(['email' => 'correo@ejemplo.com']);

    Livewire::test(IniciarSesion::class)
        ->set('email', 'correo@ejemplo.com')
        ->set('password', 'contraseña-incorrecta')
        ->call('iniciar')
        ->assertHasErrors(['email' => 'Correo o contraseña incorrectos.']);
});

it('RF-11: usuario inactivo muestra el mismo mensaje genérico', function () {
    Usuario::factory()->create([
        'email'  => 'inactivo@ejemplo.com',
        'activo' => false,
    ]);

    Livewire::test(IniciarSesion::class)
        ->set('email', 'inactivo@ejemplo.com')
        ->set('password', 'password')
        ->call('iniciar')
        ->assertHasErrors(['email' => 'Correo o contraseña incorrectos.']);
});

it('SEG-02: bloquea tras 5 intentos fallidos por correo', function () {
    Usuario::factory()->create(['email' => 'limite@ejemplo.com']);

    $component = Livewire::test(IniciarSesion::class)
        ->set('email', 'limite@ejemplo.com');

    foreach (range(1, 5) as $_) {
        $component->set('password', 'mal')->call('iniciar');
    }

    $component->set('password', 'mal')
        ->call('iniciar')
        ->assertHasErrors(['email']);

    expect(
        RateLimiter::tooManyAttempts('login_email:limite@ejemplo.com', 5)
    )->toBeTrue();
});

it('SEG-01: la recuperación exige contraseña de al menos 12 caracteres', function () {
    $usuario = Usuario::factory()->create();

    Livewire::test(\App\Livewire\Auth\RestablecerContrasena::class, ['token' => 'tok'])
        ->set('email', $usuario->email)
        ->set('password', 'corta')
        ->set('password_confirmation', 'corta')
        ->call('restablecer')
        ->assertHasErrors(['password']);
});

it('RF-13: recuperación siempre responde con el mismo mensaje', function () {
    Livewire::test(OlvideMiContrasena::class)
        ->set('email', 'noexiste@ejemplo.com')
        ->call('enviar')
        ->assertSet('enviado', true);
});
