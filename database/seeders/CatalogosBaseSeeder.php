<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Catálogos de arranque aptos para producción (TDD sección 19, E-03). */
class CatalogosBaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->categorias();
        $this->personas();
        $this->cuentas();
        $this->esquemaReparto();
    }

    private function categorias(): void
    {
        DB::table('categorias')->insertOrIgnore([
            ['nombre' => 'CONCRETO',                          'grupo' => 'obra'],
            ['nombre' => 'MAQUINARIA',                        'grupo' => 'obra'],
            ['nombre' => 'MATERIALES DE OBRA',                'grupo' => 'obra'],
            ['nombre' => 'FERRETERIA',                        'grupo' => 'obra'],
            ['nombre' => 'LABORATORIO',                       'grupo' => 'obra'],
            ['nombre' => 'TOPOGRAFIA',                        'grupo' => 'obra'],
            ['nombre' => 'AGUA Y PIPAS',                      'grupo' => 'obra'],
            ['nombre' => 'FLETES Y REMOLQUES',                'grupo' => 'obra'],
            ['nombre' => 'EQUIPO DE SEGURIDAD',               'grupo' => 'obra'],
            ['nombre' => 'SERVICIOS DE OBRA',                 'grupo' => 'obra'],
            ['nombre' => 'NOMINA',                            'grupo' => 'nomina'],
            ['nombre' => 'FINIQUITOS',                        'grupo' => 'nomina'],
            ['nombre' => 'SAT',                               'grupo' => 'impuestos'],
            ['nombre' => 'IMSS',                              'grupo' => 'impuestos'],
            ['nombre' => 'ISR',                               'grupo' => 'impuestos'],
            ['nombre' => 'ISN',                               'grupo' => 'impuestos'],
            ['nombre' => 'IVA',                               'grupo' => 'impuestos'],
            ['nombre' => 'COMISIONES',                        'grupo' => 'comisiones'],
            ['nombre' => 'COMISION INTERMEDIARIO',            'grupo' => 'comisiones'],
            ['nombre' => 'UTILIDAD',                          'grupo' => 'reparto_utilidad'],
            ['nombre' => 'INTERESES',                         'grupo' => 'financiamiento'],
            ['nombre' => 'TRAMITES Y COMISIONES DE PRESTAMO', 'grupo' => 'financiamiento'],
            ['nombre' => 'COMBUSTIBLE',                       'grupo' => 'logistica'],
            ['nombre' => 'CASETAS',                           'grupo' => 'logistica'],
            ['nombre' => 'VEHICULOS',                         'grupo' => 'logistica'],
            ['nombre' => 'VIATICOS',                          'grupo' => 'logistica'],
            ['nombre' => 'ADMINISTRACION',                    'grupo' => 'administracion'],
            ['nombre' => 'SISTEMAS Y SERVICIOS',              'grupo' => 'administracion'],
            ['nombre' => 'FIANZAS Y SEGUROS',                 'grupo' => 'administracion'],
            ['nombre' => 'RENTAS',                            'grupo' => 'administracion'],
            ['nombre' => 'APOYOS',                            'grupo' => 'otros'],
            ['nombre' => 'VARIOS',                            'grupo' => 'otros'],
        ]);
    }

    private function personas(): void
    {
        DB::table('personas')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'Alejandra', 'es_socio' => true],
            ['id' => 2, 'nombre' => 'Javier',    'es_socio' => true],
            ['id' => 3, 'nombre' => 'Álvaro',    'es_socio' => true],
            ['id' => 4, 'nombre' => 'GPE',        'es_socio' => true],
            ['id' => 5, 'nombre' => 'Alfredo',   'es_socio' => false],
        ]);
    }

    private function cuentas(): void
    {
        DB::table('cuentas')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'Banco del Bajío (GADIT)', 'tipo' => 'banco',        'persona_id' => null, 'notas' => 'Supuesto de la carga inicial: todos los cobros entraron aquí'],
            ['id' => 2, 'nombre' => 'Caja Alejandra',          'tipo' => 'caja',         'persona_id' => 1,    'notas' => null],
            ['id' => 3, 'nombre' => 'Alfredo (intermediario)', 'tipo' => 'intermediario', 'persona_id' => 5,    'notas' => null],
            ['id' => 4, 'nombre' => 'Caja GPE',                'tipo' => 'caja',         'persona_id' => 4,    'notas' => null],
            ['id' => 5, 'nombre' => 'Caja Javier',             'tipo' => 'caja',         'persona_id' => 2,    'notas' => null],
        ]);
    }

    private function esquemaReparto(): void
    {
        DB::table('esquemas_reparto')->insertOrIgnore([
            'id' => 1,
            'nombre' => 'Reparto general (CONCENTRADO)',
            'notas' => 'Simulación del Excel: 30 / 25 / 22.5 / 22.5',
        ]);

        DB::table('esquema_reparto_socios')->insertOrIgnore([
            ['esquema_id' => 1, 'persona_id' => 1, 'porcentaje' => 30.00],
            ['esquema_id' => 1, 'persona_id' => 2, 'porcentaje' => 25.00],
            ['esquema_id' => 1, 'persona_id' => 3, 'porcentaje' => 22.50],
            ['esquema_id' => 1, 'persona_id' => 4, 'porcentaje' => 22.50],
        ]);
    }
}
