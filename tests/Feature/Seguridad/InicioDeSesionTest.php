<?php

use App\Livewire\Auth\IniciarSesion;
use App\Models\Usuario;
use Livewire\Livewire;

it('RF-11: al entrar va a la dirección que se intentaba abrir', function () {
    $usuario = Usuario::factory()->create();
    session(['url.intended' => url('/perfil')]);

    Livewire::test(IniciarSesion::class)
        ->set('email', $usuario->email)
        ->set('password', 'password')
        ->call('iniciar')
        ->assertRedirect(url('/perfil'));
});

it('RF-11: la ruta del tablero responde a un usuario con sesión', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)->get(route('tablero'))->assertOk();
});

it('RF-11: un invitado que pide el tablero va al inicio de sesión', function () {
    $this->get('/tablero')->assertRedirect(route('login'));
});
