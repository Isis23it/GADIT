<?php

use App\Enums\RolUsuario;
use App\Models\Usuario;

// Fuente de verdad de la matriz de permisos (sección 6 del TDD).
// Una entrada por fila × rol. Quien cambie la matriz cambia este archivo en el mismo PR.
//
// Formato de cada entrada:
//   'modulo'   → identificador de la fila
//   'rol'      → RolUsuario que se prueba
//   'modelo'   → FQCN del modelo para prueba de política; null = pendiente
//   'ruta'     → nombre de ruta para prueba de acceso; null = pendiente
//   'livewire' → FQCN del componente para prueba de llamada directa; null = pendiente
//   'puede'    → acciones de policy que deben estar autorizadas
//   'no_puede' → acciones de policy que deben estar denegadas
//
// Al construir un módulo: rellenar modelo/ruta/livewire y los arreglos puede/no_puede.

return [

    // ── Tablero ──────────────────────────────────────────────────────────────
    ['modulo' => 'tablero', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'tablero', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'tablero', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Clientes y proyectos ─────────────────────────────────────────────────
    ['modulo' => 'clientes', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'clientes', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'clientes', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Contratos, adendas y ajustes ─────────────────────────────────────────
    ['modulo' => 'contratos', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'contratos', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'contratos', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Facturas ─────────────────────────────────────────────────────────────
    ['modulo' => 'facturas', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'facturas', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'facturas', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Cobros ───────────────────────────────────────────────────────────────
    ['modulo' => 'cobros', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'cobros', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'cobros', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Gastos ───────────────────────────────────────────────────────────────
    ['modulo' => 'gastos', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'gastos', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'gastos', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Proveedores y cuentas por pagar ──────────────────────────────────────
    ['modulo' => 'proveedores', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'proveedores', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'proveedores', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Datos bancarios ──────────────────────────────────────────────────────
    ['modulo' => 'datos_bancarios', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'datos_bancarios', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'datos_bancarios', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Personas ─────────────────────────────────────────────────────────────
    ['modulo' => 'personas', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'personas', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'personas', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Categorías ───────────────────────────────────────────────────────────
    ['modulo' => 'categorias', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'categorias', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'categorias', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Cuentas y saldo inicial ──────────────────────────────────────────────
    ['modulo' => 'cuentas', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'cuentas', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'cuentas', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Traspasos y estados de cuenta ────────────────────────────────────────
    ['modulo' => 'traspasos', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'traspasos', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'traspasos', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Préstamos y sus pagos ────────────────────────────────────────────────
    ['modulo' => 'prestamos', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'prestamos', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'prestamos', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Reparto de utilidad ──────────────────────────────────────────────────
    ['modulo' => 'reparto', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'reparto', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'reparto', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Adjuntos ─────────────────────────────────────────────────────────────
    ['modulo' => 'adjuntos', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'adjuntos', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'adjuntos', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Reportes y exportaciones ─────────────────────────────────────────────
    ['modulo' => 'reportes', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'reportes', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'reportes', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Restaurar registros borrados ─────────────────────────────────────────
    ['modulo' => 'restaurar', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'restaurar', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'restaurar', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Bitácora ─────────────────────────────────────────────────────────────
    ['modulo' => 'bitacora', 'rol' => RolUsuario::Admin,    'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'bitacora', 'rol' => RolUsuario::Captura,  'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],
    ['modulo' => 'bitacora', 'rol' => RolUsuario::Consulta, 'modelo' => null, 'ruta' => null, 'livewire' => null, 'puede' => [], 'no_puede' => []],

    // ── Usuarios (único modelo construido en este hito) ───────────────────────
    [
        'modulo' => 'usuarios',
        'rol' => RolUsuario::Admin,
        'modelo' => Usuario::class,
        'ruta' => null,  // activar en GAD-0.4.4 (pantalla Usuarios)
        'livewire' => null,
        'puede' => ['viewAny', 'view', 'create', 'update', 'delete', 'restore'],
        'no_puede' => [],
    ],
    [
        'modulo' => 'usuarios',
        'rol' => RolUsuario::Captura,
        'modelo' => Usuario::class,
        'ruta' => null,
        'livewire' => null,
        'puede' => [],
        'no_puede' => ['viewAny', 'view', 'create', 'update', 'delete', 'restore'],
    ],
    [
        'modulo' => 'usuarios',
        'rol' => RolUsuario::Consulta,
        'modelo' => Usuario::class,
        'ruta' => null,
        'livewire' => null,
        'puede' => [],
        'no_puede' => ['viewAny', 'view', 'create', 'update', 'delete', 'restore'],
    ],
];
