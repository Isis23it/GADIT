<?php

use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('siembra personas, cuentas, categorías y el esquema de reparto', function () {
    $this->seed(CatalogosBaseSeeder::class);

    expect(DB::table('categorias')->count())->toBe(32)
        ->and(DB::table('personas')->count())->toBe(5)
        ->and(DB::table('personas')->where('es_socio', 1)->count())->toBe(4)
        ->and(DB::table('cuentas')->count())->toBe(4)
        ->and(DB::table('esquemas_reparto')->count())->toBe(1)
        ->and(DB::table('esquema_reparto_socios')->count())->toBe(4);
});

it('deja el reparto en 30, 25, 22.5 y 22.5 sumando 100', function () {
    $this->seed(CatalogosBaseSeeder::class);

    $porcentajes = DB::table('esquema_reparto_socios')
        ->join('personas', 'personas.id', '=', 'esquema_reparto_socios.persona_id')
        ->pluck('esquema_reparto_socios.porcentaje', 'personas.nombre')
        ->map(fn ($p) => (string) $p)
        ->all();

    expect($porcentajes)->toBe([
        'Alejandra' => '30.00',
        'Javier' => '25.00',
        'Álvaro' => '22.50',
        'GPE' => '22.50',
    ])->and((string) DB::table('esquema_reparto_socios')->sum('porcentaje'))->toBe('100.00');
});

it('se puede correr dos veces sin duplicar filas', function () {
    $this->seed(CatalogosBaseSeeder::class);
    $this->seed(CatalogosBaseSeeder::class);

    expect(DB::table('categorias')->count())->toBe(32)
        ->and(DB::table('personas')->count())->toBe(5)
        ->and(DB::table('cuentas')->count())->toBe(4)
        ->and(DB::table('esquemas_reparto')->count())->toBe(1)
        ->and(DB::table('esquema_reparto_socios')->count())->toBe(4);
});

it('no pisa lo que el dueño haya editado', function () {
    $this->seed(CatalogosBaseSeeder::class);

    DB::table('categorias')->where('nombre', 'VARIOS')->update(['activa' => 0]);
    DB::table('esquema_reparto_socios')->where('porcentaje', '30.00')->update(['porcentaje' => '40.00']);

    $this->seed(CatalogosBaseSeeder::class);

    expect((int) DB::table('categorias')->where('nombre', 'VARIOS')->value('activa'))->toBe(0)
        ->and(DB::table('esquema_reparto_socios')->where('porcentaje', '40.00')->count())->toBe(1);
});

it('enlaza las cuentas con su persona por nombre y no por id fijo', function () {
    DB::table('personas')->insert(['nombre' => 'Persona previa', 'es_socio' => 0]);

    $this->seed(CatalogosBaseSeeder::class);

    $custodios = DB::table('cuentas')
        ->leftJoin('personas', 'personas.id', '=', 'cuentas.persona_id')
        ->select('cuentas.nombre as cuenta', 'personas.nombre as custodio')
        ->pluck('custodio', 'cuenta')
        ->all();

    expect($custodios)->toBe([
        'Banco del Bajío (GADIT)' => null,
        'Caja Alejandra' => 'Alejandra',
        'Alfredo (intermediario)' => 'Alfredo',
        'Caja GPE' => 'GPE',
    ]);
});
