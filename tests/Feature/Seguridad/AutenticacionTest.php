<?php

use App\Livewire\Auth\IniciarSesion;
use App\Livewire\Auth\OlvideMiContrasena;
use App\Livewire\Auth\RestablecerContrasena;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

// Requiere tabla `usuarios` y `sessions` — pendiente de migraciones GAD-29
$skip = 'Pendiente de migraciones GAD-29 (tabla usuarios)';

beforeEach(function () {
    Carbon::setTestNow('2026-03-16 10:00', 'America/Mexico_City');
    RateLimiter::clear('login_email:test@example.com');
    RateLimiter::clear('login_ip:127.0.0.1');
});

it('RF-11: muestra la pantalla de inicio de sesión', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSeeLivewire(IniciarSesion::class);
})->skip($skip);

it('RF-11: redirige al tablero tras credenciales correctas', function () {
    $usuario = Usuario::factory()->create();

    Livewire::test(IniciarSesion::class)
        ->set('email', $usuario->email)
        ->set('password', 'password')
        ->call('iniciar')
        ->assertRedirect(route('tablero'));

    $this->assertAuthenticatedAs($usuario);
})->skip($skip);

it('RF-11: credenciales incorrectas muestran mensaje genérico', function () {
    Usuario::factory()->create(['email' => 'correo@ejemplo.com']);

    Livewire::test(IniciarSesion::class)
        ->set('email', 'correo@ejemplo.com')
        ->set('password', 'contraseña-incorrecta')
        ->call('iniciar')
        ->assertHasErrors(['email' => 'Correo o contraseña incorrectos.']);
})->skip($skip);

it('RF-11: usuario inactivo muestra el mismo mensaje genérico', function () {
    Usuario::factory()->create([
        'email' => 'inactivo@ejemplo.com',
        'activo' => false,
    ]);

    Livewire::test(IniciarSesion::class)
        ->set('email', 'inactivo@ejemplo.com')
        ->set('password', 'password')
        ->call('iniciar')
        ->assertHasErrors(['email' => 'Correo o contraseña incorrectos.']);
})->skip($skip);

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
})->skip($skip);

it('SEG-01: la recuperación exige contraseña de al menos 12 caracteres', function () {
    $usuario = Usuario::factory()->create();

    Livewire::test(RestablecerContrasena::class, ['token' => 'tok'])
        ->set('email', $usuario->email)
        ->set('password', 'corta')
        ->set('password_confirmation', 'corta')
        ->call('restablecer')
        ->assertHasErrors(['password']);
})->skip($skip);

it('RF-13: recuperación siempre responde con el mismo mensaje', function () {
    Livewire::test(OlvideMiContrasena::class)
        ->set('email', 'noexiste@ejemplo.com')
        ->call('enviar')
        ->assertSet('enviado', true);
})->skip($skip);
