<?php

use App\Livewire\Auth\MiPerfil;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    // uncompromised() consulta una API externa: se simula sin coincidencias.
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
});

it('RF-14: un invitado que pide el perfil va al inicio de sesión', function () {
    $this->get('/perfil')->assertRedirect(route('login'));
});

it('RF-14: la pantalla muestra el nombre editable y el correo sin editar', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('perfil'))
        ->assertOk()
        ->assertSeeLivewire(MiPerfil::class)
        ->assertSee($usuario->email);
});

it('RF-14: cambia el nombre y no toca el correo ni el rol', function () {
    $usuario = Usuario::factory()->create(['nombre' => 'Nombre Viejo']);
    $correo = $usuario->email;
    $rol = $usuario->rol;

    Livewire::actingAs($usuario)->test(MiPerfil::class)
        ->set('nombre', 'Nombre Nuevo')
        ->call('guardarNombre')
        ->assertHasNoErrors()
        ->assertDispatched('aviso');

    $usuario->refresh();

    expect($usuario->nombre)->toBe('Nombre Nuevo')
        ->and($usuario->email)->toBe($correo)
        ->and($usuario->rol)->toBe($rol);
});

it('RF-14: el nombre es obligatorio', function () {
    $usuario = Usuario::factory()->create();

    Livewire::actingAs($usuario)->test(MiPerfil::class)
        ->set('nombre', '   ')
        ->call('guardarNombre')
        ->assertHasErrors(['nombre']);
});

it('RF-14: cambiar la contraseña pide la actual', function () {
    $usuario = Usuario::factory()->create();

    Livewire::actingAs($usuario)->test(MiPerfil::class)
        ->set('password_actual', 'equivocada')
        ->set('password', 'una-clave-larga-y-unica-2026')
        ->set('password_confirmation', 'una-clave-larga-y-unica-2026')
        ->call('cambiarContrasena')
        ->assertHasErrors(['password_actual']);

    expect(Hash::check('password', $usuario->refresh()->password))->toBeTrue();
});

it('SEG-01: la nueva contraseña de menos de 12 caracteres se rechaza en Mi perfil', function () {
    $usuario = Usuario::factory()->create();

    Livewire::actingAs($usuario)->test(MiPerfil::class)
        ->set('password_actual', 'password')
        ->set('password', 'corta')
        ->set('password_confirmation', 'corta')
        ->call('cambiarContrasena')
        ->assertHasErrors(['password']);
});

it('RF-14: la confirmación debe coincidir con la nueva contraseña', function () {
    $usuario = Usuario::factory()->create();

    Livewire::actingAs($usuario)->test(MiPerfil::class)
        ->set('password_actual', 'password')
        ->set('password', 'una-clave-larga-y-unica-2026')
        ->set('password_confirmation', 'otra-clave-distinta-2026')
        ->call('cambiarContrasena')
        ->assertHasErrors(['password']);
});

it('RF-14: con la actual correcta cambia la contraseña y limpia los campos', function () {
    $usuario = Usuario::factory()->create();

    Livewire::actingAs($usuario)->test(MiPerfil::class)
        ->set('password_actual', 'password')
        ->set('password', 'una-clave-larga-y-unica-2026')
        ->set('password_confirmation', 'una-clave-larga-y-unica-2026')
        ->call('cambiarContrasena')
        ->assertHasNoErrors()
        ->assertDispatched('aviso')
        ->assertSet('password', '')
        ->assertSet('password_actual', '');

    expect(Hash::check('una-clave-larga-y-unica-2026', $usuario->refresh()->password))->toBeTrue();
});

it('RF-14: el menú de usuario enlaza a /perfil', function () {
    $usuario = Usuario::factory()->create();

    $html = (string) $this->blade('<x-menu-usuario :usuario="$usuario" />', ['usuario' => $usuario]);

    expect($html)->toContain(route('perfil'))
        ->and($html)->not->toContain('mi-perfil');
});
