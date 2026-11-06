<?php

use App\Enums\RolUsuario;
use App\Support\Menu;

/** @return list<string> */
function rutasDelMenu(RolUsuario $rol): array
{
    return collect(Menu::para($rol))->pluck('opciones')->flatten(1)->pluck('ruta')->all();
}

it('RF-80: el administrador ve las 20 opciones en los 7 grupos', function () {
    expect(rutasDelMenu(RolUsuario::Admin))->toHaveCount(20)
        ->and(collect(Menu::para(RolUsuario::Admin))->pluck('grupo')->all())
        ->toBe(['Inicio', 'Ingresos', 'Egresos', 'Dinero', 'Catálogos', 'Reportes', 'Administración']);
});

it('RF-80: captura ve 13 opciones y ninguna de dinero ni de administración', function () {
    $rutas = rutasDelMenu(RolUsuario::Captura);

    expect($rutas)->toHaveCount(13)
        ->and($rutas)->not->toContain('prestamos', 'cuentas', 'traspasos', 'reparto', 'usuarios', 'bitacora', 'papelera')
        ->and($rutas)->toContain('gastos', 'cuentas-por-pagar', 'reportes')
        ->and(collect(Menu::para(RolUsuario::Captura))->pluck('grupo')->all())
        ->not->toContain('Dinero', 'Administración');
});

it('RF-80: consulta ve 17 opciones y nada de administración', function () {
    $rutas = rutasDelMenu(RolUsuario::Consulta);

    expect($rutas)->toHaveCount(17)
        ->and($rutas)->not->toContain('usuarios', 'bitacora', 'papelera')
        ->and($rutas)->toContain('prestamos', 'cuentas', 'traspasos', 'reparto');
});

it('RF-80: la opción actual es la ruta exacta o una de sus pantallas internas', function () {
    expect(Menu::esActual('gastos', 'gastos'))->toBeTrue()
        ->and(Menu::esActual('gastos/123', 'gastos'))->toBeTrue()
        ->and(Menu::esActual('/gastos/', 'gastos'))->toBeTrue()
        ->and(Menu::esActual('facturas', 'gastos'))->toBeFalse();
});

it('RF-80: cuentas no se marca cuando se está en cuentas por pagar', function () {
    expect(Menu::esActual('cuentas-por-pagar', 'cuentas'))->toBeFalse()
        ->and(Menu::esActual('cuentas-por-pagar', 'cuentas-por-pagar'))->toBeTrue();
});
