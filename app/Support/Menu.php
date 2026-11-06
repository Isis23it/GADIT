<?php

namespace App\Support;

use App\Enums\RolUsuario;

/** Menú lateral por rol (RF-80): un grupo por sección y, en cada opción, los roles que la ven. */
final class Menu
{
    /**
     * @return list<array{grupo: string, opciones: list<array{etiqueta: string, ruta: string}>}>
     */
    public static function para(RolUsuario $rol): array
    {
        $menu = [];

        foreach (self::definicion() as $grupo => $opciones) {
            $visibles = [];

            foreach ($opciones as [$etiqueta, $ruta, $roles]) {
                if (in_array($rol, $roles, true)) {
                    $visibles[] = ['etiqueta' => $etiqueta, 'ruta' => $ruta];
                }
            }

            if ($visibles !== []) {
                $menu[] = ['grupo' => $grupo, 'opciones' => $visibles];
            }
        }

        return $menu;
    }

    /** La opción está activa en su ruta y en las de abajo ("cuentas" no activa "cuentas-por-pagar"). */
    public static function esActual(string $actual, string $ruta): bool
    {
        $actual = trim($actual, '/');

        return $actual === $ruta || str_starts_with($actual, $ruta.'/');
    }

    /**
     * @return array<string, list<array{0: string, 1: string, 2: list<RolUsuario>}>>
     */
    private static function definicion(): array
    {
        $todos = [RolUsuario::Admin, RolUsuario::Captura, RolUsuario::Consulta];
        $sinCaptura = [RolUsuario::Admin, RolUsuario::Consulta];
        $soloAdmin = [RolUsuario::Admin];

        return [
            'Inicio' => [
                ['Tablero', 'tablero', $todos],
            ],
            'Ingresos' => [
                ['Contratos', 'contratos', $todos],
                ['Facturas', 'facturas', $todos],
                ['Cobros', 'cobros', $todos],
                ['Complementos pendientes', 'complementos-pendientes', $todos],
            ],
            'Egresos' => [
                ['Gastos', 'gastos', $todos],
                ['Cuentas por pagar', 'cuentas-por-pagar', $todos],
                ['Préstamos', 'prestamos', $sinCaptura],
            ],
            'Dinero' => [
                ['Cuentas y estados de cuenta', 'cuentas', $sinCaptura],
                ['Traspasos', 'traspasos', $sinCaptura],
                ['Reparto de utilidad', 'reparto', $sinCaptura],
            ],
            'Catálogos' => [
                ['Clientes', 'clientes', $todos],
                ['Proyectos', 'proyectos', $todos],
                ['Proveedores', 'proveedores', $todos],
                ['Personas', 'personas', $todos],
                ['Categorías', 'categorias', $todos],
            ],
            'Reportes' => [
                ['Reportes', 'reportes', $todos],
            ],
            'Administración' => [
                ['Usuarios', 'usuarios', $soloAdmin],
                ['Bitácora', 'bitacora', $soloAdmin],
                ['Papelera', 'papelera', $soloAdmin],
            ],
        ];
    }
}
