<?php

use App\Livewire\Auth\IniciarSesion;
use App\Livewire\Auth\MiPerfil;
use App\Models\Usuario;
use Livewire\Livewire;

beforeEach(function () {
    app()->setLocale('es');
});

it('RF-85: los campos obligatorios del login llevan asterisco rojo y aria-required', function () {
    $html = Livewire::test(IniciarSesion::class)->html();

    expect(substr_count($html, '<span class="text-red-600" aria-hidden="true"> *</span>'))->toBe(2)
        ->and(substr_count($html, 'aria-required="true"'))->toBe(2);
});

it('RF-85: los campos obligatorios de Mi perfil llevan asterisco rojo y aria-required', function () {
    $html = Livewire::actingAs(Usuario::factory()->create())->test(MiPerfil::class)->html();

    expect(substr_count($html, '<span class="text-red-600" aria-hidden="true"> *</span>'))->toBe(4)
        ->and(substr_count($html, 'aria-required="true"'))->toBe(4);
});

it('RF-85: los errores del login llevan icono además del texto', function () {
    $html = Livewire::test(IniciarSesion::class)->call('iniciar')->html();

    expect(substr_count($html, 'mt-0.5 h-4 w-4 shrink-0'))->toBe(2)
        ->and($html)->toContain('El campo correo electrónico es obligatorio.');
});

it('RF-85: el error de Mi perfil lleva icono y queda enlazado al campo', function () {
    Livewire::actingAs(Usuario::factory()->create())->test(MiPerfil::class)
        ->set('nombre', '')
        ->call('guardarNombre')
        ->assertSeeHtml('mt-0.5 h-4 w-4 shrink-0')
        ->assertSeeHtml('id="nombre-error"')
        ->assertSeeHtml('aria-describedby="nombre-error"');
});
