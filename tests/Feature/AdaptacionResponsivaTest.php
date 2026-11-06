<?php

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Support\HtmlString;

function htmlDelLayoutResponsivo(): string
{
    test()->withoutVite()
        ->actingAs(new Usuario(['nombre' => 'Luis Gómez', 'rol' => RolUsuario::Captura]));

    return (string) test()->view('layouts.app', ['title' => 'Gastos', 'slot' => new HtmlString('<p>Contenido</p>')]);
}

it('RF-81: por debajo de 1024 px el menú es un cajón con botón ☰ y arriba queda el lateral fijo', function () {
    $html = htmlDelLayoutResponsivo();

    expect($html)->toContain('aria-label="Abrir menú"')
        ->and($html)->toContain('id="menu-movil"')
        ->and($html)->toContain('hidden w-64 shrink-0')
        ->and($html)->toContain('lg:block')
        ->and($html)->toContain('lg:hidden');
});

it('RF-81: en móvil el cajón ocupa el 80 % del ancho', function () {
    expect(htmlDelLayoutResponsivo())->toContain('w-4/5');
});

it('RF-81: el cajón se cierra al elegir una opción, con Esc y al tocar fuera', function () {
    $html = htmlDelLayoutResponsivo();

    expect($html)->toContain("closest('a')) menuAbierto = false")
        ->and($html)->toContain('x-on:keydown.escape.window="menuAbierto = false"')
        ->and($html)->toContain('x-on:click="menuAbierto = false"');
});

it('RF-85: el cajón se anuncia como diálogo y atrapa el foco', function () {
    $html = htmlDelLayoutResponsivo();

    expect($html)->toContain('role="dialog"')
        ->and($html)->toContain('aria-modal="true"')
        ->and($html)->toContain('x-trap.noscroll');
});

it('RF-81: los botones táctiles del cajón miden al menos 44 px', function () {
    expect(substr_count(htmlDelLayoutResponsivo(), 'min-h-11 min-w-11'))->toBeGreaterThanOrEqual(2);
});

it('RF-81: el texto del menú mide 16 px en pantallas táctiles', function () {
    $html = (string) $this->blade('<x-menu-lateral :rol="$rol" actual="tablero" />', ['rol' => RolUsuario::Admin]);

    expect($html)->toContain('text-base')
        ->and($html)->toContain('lg:text-sm');
});

it('RF-81: en móvil la acción principal es un botón redondo + y desde 768 px un botón normal', function () {
    $html = (string) $this->blade('<x-boton-accion href="/contratos/crear">Nuevo contrato</x-boton-accion>');

    expect($html)->toContain('href="/contratos/crear"')
        ->and($html)->toContain('rounded-full')
        ->and($html)->toContain('bottom-4 right-4')
        ->and($html)->toContain('md:rounded-lg')
        ->and($html)->toContain('Nuevo contrato');
});

it('RF-81: la acción principal sin enlace es un botón', function () {
    $html = (string) $this->blade('<x-boton-accion wire:click="crear">Nuevo gasto</x-boton-accion>');

    expect($html)->toContain('<button type="button"')
        ->and($html)->toContain('wire:click="crear"');
});

it('RF-04: el layout incluye la región de avisos', function () {
    expect(htmlDelLayoutResponsivo())->toContain('aria-live="polite"');
});

it('RF-81: en móvil el menú de usuario va dentro del cajón y desde 768 px en la cabecera', function () {
    $html = htmlDelLayoutResponsivo();

    expect($html)->toContain('hidden md:block')
        ->and($html)->toContain('mt-auto md:hidden')
        ->and(substr_count($html, 'Cerrar sesión'))->toBe(2);
});

it('RF-81: el menú de usuario del cajón es una lista sin desplegable', function () {
    $usuario = new Usuario(['nombre' => 'Ana Pérez', 'rol' => RolUsuario::Captura]);

    $html = (string) $this->blade('<x-menu-usuario :usuario="$usuario" :plano="true" />', ['usuario' => $usuario]);

    expect($html)->toContain('Ana Pérez')
        ->and($html)->toContain('Mi perfil')
        ->and($html)->toMatch('/<form method="POST" action="[^"]*cerrar-sesion"/')
        ->and($html)->not->toContain('aria-haspopup');
});
