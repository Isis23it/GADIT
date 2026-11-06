<?php

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Support\HtmlString;

it('RF-80: el administrador ve Administración y Dinero', function () {
    $this->blade('<x-menu-lateral :rol="$rol" actual="tablero" />', ['rol' => RolUsuario::Admin])
        ->assertSee('Administración')
        ->assertSee('Usuarios')
        ->assertSee('Bitácora')
        ->assertSee('Papelera')
        ->assertSee('Traspasos');
});

it('RF-80: captura no ve Dinero ni Administración ni préstamos', function () {
    $this->blade('<x-menu-lateral :rol="$rol" actual="tablero" />', ['rol' => RolUsuario::Captura])
        ->assertSee('Gastos')
        ->assertSee('Cuentas por pagar')
        ->assertDontSee('Dinero')
        ->assertDontSee('Administración')
        ->assertDontSee('Préstamos')
        ->assertDontSee('Traspasos');
});

it('RF-80: consulta ve Dinero pero no Administración', function () {
    $this->blade('<x-menu-lateral :rol="$rol" actual="tablero" />', ['rol' => RolUsuario::Consulta])
        ->assertSee('Traspasos')
        ->assertDontSee('Administración')
        ->assertDontSee('Bitácora');
});

it('RF-80: la opción de la pantalla actual lleva aria-current y las demás no', function () {
    $html = (string) $this->blade('<x-menu-lateral :rol="$rol" actual="gastos/15" />', ['rol' => RolUsuario::Admin]);

    expect(substr_count($html, 'aria-current="page"'))->toBe(1)
        ->and($html)->toMatch('/href="[^"]*\/gastos"\s+aria-current="page"/');
});

it('RF-80: el contador rojo solo aparece cuando es mayor que cero', function () {
    $con = (string) $this->blade(
        '<x-menu-lateral :rol="$rol" actual="tablero" :contadores="$contadores" />',
        ['rol' => RolUsuario::Admin, 'contadores' => ['facturas' => 3, 'gastos' => 0]],
    );
    $sin = (string) $this->blade('<x-menu-lateral :rol="$rol" actual="tablero" />', ['rol' => RolUsuario::Admin]);

    expect($con)->toMatch('/Facturas<\/span>\s*<span[^>]*>\s*3/')
        ->and(substr_count($con, 'bg-red-700'))->toBe(1)
        ->and($sin)->not->toContain('bg-red-700');
});

it('RF-80: el menú de usuario muestra nombre, rol, Mi perfil y Cerrar sesión por POST', function () {
    $usuario = new Usuario(['nombre' => 'Ana Pérez', 'rol' => RolUsuario::Captura]);

    $html = (string) $this->blade('<x-menu-usuario :usuario="$usuario" />', ['usuario' => $usuario]);

    expect($html)->toContain('Ana Pérez')
        ->and($html)->toContain(RolUsuario::Captura->label())
        ->and($html)->toContain('Mi perfil')
        ->and($html)->toContain('Cerrar sesión')
        ->and($html)->toMatch('/<form method="POST" action="[^"]*cerrar-sesion"/');
});

it('RF-80: el layout principal arma menú del rol, título y contenido', function () {
    $this->withoutVite()
        ->actingAs(new Usuario(['nombre' => 'Luis Gómez', 'rol' => RolUsuario::Consulta]));

    $this->view('layouts.app', ['title' => 'Gastos', 'slot' => new HtmlString('<p>Contenido de prueba</p>')])
        ->assertSee('GADIT')
        ->assertSee('Gastos')
        ->assertSee('Luis Gómez')
        ->assertSee('Contenido de prueba', false)
        ->assertSee('Traspasos')
        ->assertDontSee('Administración');
});
