<?php

use App\Livewire\Auth\IniciarSesion;
use Livewire\Livewire;

it('RF-84: los errores del login salen en español', function () {
    Livewire::test(IniciarSesion::class)
        ->set('email', '')
        ->set('password', '')
        ->call('iniciar')
        ->assertSee('El campo correo electrónico es obligatorio.')
        ->assertSee('El campo contraseña es obligatorio.')
        ->assertDontSee('field is required');
});

it('RF-84: un correo mal escrito se rechaza en español', function () {
    Livewire::test(IniciarSesion::class)
        ->set('email', 'no-es-correo')
        ->set('password', 'algo')
        ->call('iniciar')
        ->assertSee('no es un correo válido');
});

it('RF-85: los errores del login quedan enlazados al campo', function () {
    Livewire::test(IniciarSesion::class)
        ->call('iniciar')
        ->assertSeeHtml('id="email-error"')
        ->assertSeeHtml('aria-describedby="email-error"')
        ->assertSeeHtml('id="password-error"')
        ->assertSeeHtml('aria-describedby="password-error"')
        ->assertSeeHtml('aria-invalid="true"');
});

it('RF-85: sin errores los campos no se marcan como inválidos', function () {
    Livewire::test(IniciarSesion::class)
        ->assertDontSeeHtml('aria-invalid="true"');
});

it('RF-81: los controles del login miden al menos 44 px', function () {
    Livewire::test(IniciarSesion::class)
        ->assertSeeHtml('min-h-11')
        ->assertSeeHtml('min-w-11');
});

it('RF-85: la pantalla de acceso tiene un landmark main', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<main', false)
        ->assertSee('</main>', false);
});
