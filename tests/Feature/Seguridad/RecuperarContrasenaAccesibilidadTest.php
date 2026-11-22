<?php

use App\Livewire\Auth\OlvideMiContrasena;
use Livewire\Livewire;

beforeEach(function () {
    app()->setLocale('es');
});

it('RF-81: recuperar contraseña tiene controles de 44 px y el campo obligatorio marcado', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('min-h-11', false)
        ->assertSee('aria-required="true"', false)
        ->assertSee('<span class="text-red-600" aria-hidden="true"> *</span>', false);
});

it('RF-85: el error de recuperar contraseña queda enlazado al campo y lleva icono', function () {
    Livewire::test(OlvideMiContrasena::class)
        ->set('email', '')
        ->call('enviar')
        ->assertSee('El campo correo electrónico es obligatorio.')
        ->assertSeeHtml('id="email-error"')
        ->assertSeeHtml('aria-describedby="email-error"')
        ->assertSeeHtml('mt-0.5 h-4 w-4 shrink-0');
});
