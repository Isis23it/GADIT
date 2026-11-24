<?php

use App\Livewire\Auth\RestablecerContrasena;
use Livewire\Livewire;

beforeEach(function () {
    app()->setLocale('es');
});

it('RF-81: restablecer contraseña tiene controles de 44 px y los tres campos marcados', function () {
    $html = $this->get(route('password.reset', ['token' => 'abc']))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, '<span class="text-red-600" aria-hidden="true"> *</span>'))->toBe(3)
        ->and(substr_count($html, 'aria-required="true"'))->toBe(3)
        ->and($html)->toContain('min-h-11')
        ->and($html)->toContain('min-w-11');
});

it('RF-85: los errores de restablecer quedan enlazados al campo y llevan icono', function () {
    $html = Livewire::test(RestablecerContrasena::class, ['token' => 'abc'])
        ->set('email', '')
        ->set('password', '')
        ->call('restablecer')
        ->html();

    expect($html)->toContain('id="email-error"')
        ->and($html)->toContain('aria-describedby="email-error"')
        ->and($html)->toContain('id="password-error"')
        ->and($html)->toMatch('/aria-describedby="[^"]*password-error/')
        ->and(substr_count($html, 'mt-0.5 h-4 w-4 shrink-0'))->toBe(2)
        ->and($html)->toContain('El campo correo electrónico es obligatorio.');
});

it('RF-85: la ayuda del mínimo de la contraseña queda enlazada al campo', function () {
    $this->get(route('password.reset', ['token' => 'abc']))
        ->assertOk()
        ->assertSee('id="password-ayuda"', false)
        ->assertSee('Mínimo 12 caracteres.')
        ->assertSee('aria-describedby="password-ayuda', false);
});
