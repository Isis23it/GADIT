<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Datos de demostración — solo para entornos locales y de pruebas (TDD sección 19, E-18). */
class UsuariosDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('UsuariosDemoSeeder no puede ejecutarse en producción (E-18).');
        }

        $this->call(CatalogosBaseSeeder::class);

        $this->usuariosDemo();
        $this->clientesYProyectos();
        $this->concentrado();
    }

    private function usuariosDemo(): void
    {
        if (! Usuario::where('email', 'admin@gadit.test')->exists()) {
            Usuario::factory()->admin()->create(['nombre' => 'Admin Demo', 'email' => 'admin@gadit.test']);
        }

        if (! Usuario::where('email', 'captura@gadit.test')->exists()) {
            Usuario::factory()->create(['nombre' => 'Captura Demo', 'email' => 'captura@gadit.test']);
        }

        if (! Usuario::where('email', 'consulta@gadit.test')->exists()) {
            Usuario::factory()->consulta()->create(['nombre' => 'Consulta Demo', 'email' => 'consulta@gadit.test']);
        }
    }

    private function clientesYProyectos(): void
    {
        DB::table('clientes')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'IBERDROLA BAJIO'],
            ['id' => 2, 'nombre' => 'IBERDROLA CENTRO'],
            ['id' => 3, 'nombre' => 'SOLARIG'],
            ['id' => 4, 'nombre' => 'EOSOL ENERGY'],
            ['id' => 5, 'nombre' => 'PEREN'],
        ]);

        DB::table('proyectos')->insertOrIgnore([
            ['id' => 1, 'cliente_id' => 1, 'nombre' => 'Pavimentación camino de acceso'],
            ['id' => 2, 'cliente_id' => 2, 'nombre' => 'Caseta'],
            ['id' => 3, 'cliente_id' => 1, 'nombre' => 'Renta de maquinaria'],
            ['id' => 4, 'cliente_id' => 3, 'nombre' => 'Solarig'],
            ['id' => 5, 'cliente_id' => 4, 'nombre' => 'Eosol Energy'],
            ['id' => 6, 'cliente_id' => 5, 'nombre' => 'Caminos SLP'],
        ]);
    }

    private function concentrado(): void
    {
        if (DB::table('contratos')->exists()) {
            return;
        }

        DB::table('contratos')->insert([
            ['id' => 1, 'proyecto_id' => 1, 'contrato_padre_id' => null, 'tipo' => 'contrato', 'numero_pedido' => '4506816475',                    'concepto' => 'PAVIMENTACIÓN CAMINO DE ACCESO',       'importe' => '8099620.57'],
            ['id' => 2, 'proyecto_id' => 1, 'contrato_padre_id' => 1,    'tipo' => 'adenda',   'numero_pedido' => '4506816475',                    'concepto' => 'ADENDA PAVIMENTACIÓN CAMINO DE ACCESO', 'importe' => '5156651.00'],
            ['id' => 3, 'proyecto_id' => 2, 'contrato_padre_id' => null, 'tipo' => 'contrato', 'numero_pedido' => '4506813047',                    'concepto' => 'CASETA',                               'importe' => '448439.32'],
            ['id' => 4, 'proyecto_id' => 3, 'contrato_padre_id' => null, 'tipo' => 'contrato', 'numero_pedido' => '4506893670',                    'concepto' => 'RENTA MAQUINARIA',                     'importe' => '116680.00'],
            ['id' => 5, 'proyecto_id' => 4, 'contrato_padre_id' => null, 'tipo' => 'contrato', 'numero_pedido' => '00494',                         'concepto' => 'SOLARIG',                              'importe' => '700044.00'],
            ['id' => 6, 'proyecto_id' => 4, 'contrato_padre_id' => null, 'tipo' => 'contrato', 'numero_pedido' => null,                            'concepto' => 'VIAJES (SOLARIG)',                     'importe' => '300000.00'],
            ['id' => 7, 'proyecto_id' => 5, 'contrato_padre_id' => null, 'tipo' => 'contrato', 'numero_pedido' => 'DC-PR-479_IN-DI-CA-PG-02-R01', 'concepto' => 'EOSOL ENERGY',                         'importe' => '154700.00'],
            ['id' => 8, 'proyecto_id' => 6, 'contrato_padre_id' => null, 'tipo' => 'contrato', 'numero_pedido' => null,                            'concepto' => 'CAMINOS SLP (PEREN)',                  'importe' => '2953284.98'],
        ]);

        DB::table('facturas')->insert([
            ['contrato_id' => 1, 'folio' => 'A5001', 'tipo' => 'estimacion', 'numero_estimacion' => 1,    'concepto' => 'ESTIMACION I PAVIMENTACION CAMINO DE ACCESO',    'fecha_emision' => '2024-11-04', 'fecha_vencimiento' => '2024-11-12', 'importe' => '5029866.82', 'retencion_pct' => '0.00'],
            ['contrato_id' => 1, 'folio' => 'A5005', 'tipo' => 'estimacion', 'numero_estimacion' => 2,    'concepto' => 'ESTIMACION II PAVIMENTACION CAMINO DE ACCESO',   'fecha_emision' => '2024-11-11', 'fecha_vencimiento' => '2024-11-25', 'importe' => '2176976.85', 'retencion_pct' => '0.00'],
            ['contrato_id' => 1, 'folio' => 'A5006', 'tipo' => 'estimacion', 'numero_estimacion' => 3,    'concepto' => 'ESTIMACION III PAVIMENTACION CAMINO DE ACCESO',  'fecha_emision' => '2024-11-11', 'fecha_vencimiento' => '2024-11-25', 'importe' => '892776.90',  'retencion_pct' => '0.00'],
            ['contrato_id' => 4, 'folio' => 'A5007', 'tipo' => 'otro',       'numero_estimacion' => null, 'concepto' => 'RENTA MAQUINARIA',                               'fecha_emision' => '2024-11-11', 'fecha_vencimiento' => '2024-11-25', 'importe' => '116680.00',  'retencion_pct' => '0.00'],
            ['contrato_id' => 3, 'folio' => 'A5008', 'tipo' => 'otro',       'numero_estimacion' => null, 'concepto' => 'CIERRE ECONOMICO CASETA',                        'fecha_emision' => '2024-11-11', 'fecha_vencimiento' => '2024-11-25', 'importe' => '448439.32',  'retencion_pct' => '0.00'],
            ['contrato_id' => 5, 'folio' => 'A5010', 'tipo' => 'otro',       'numero_estimacion' => null, 'concepto' => 'SOLARIG',                                        'fecha_emision' => '2024-11-14', 'fecha_vencimiento' => '2024-11-28', 'importe' => '700044.00',  'retencion_pct' => '0.00'],
            ['contrato_id' => 7, 'folio' => 'A5012', 'tipo' => 'otro',       'numero_estimacion' => null, 'concepto' => 'EOSOL ENERGY',                                   'fecha_emision' => '2024-12-05', 'fecha_vencimiento' => '2025-01-05', 'importe' => '154700.00',  'retencion_pct' => '0.00'],
            ['contrato_id' => 8, 'folio' => 'A5014', 'tipo' => 'anticipo',   'numero_estimacion' => null, 'concepto' => 'PEREN CAMINOS SLP ANTICIPO',                     'fecha_emision' => '2024-12-11', 'fecha_vencimiento' => '2025-01-11', 'importe' => '738321.24',  'retencion_pct' => '0.00'],
            ['contrato_id' => 8, 'folio' => 'A5016', 'tipo' => 'estimacion', 'numero_estimacion' => 1,    'concepto' => 'PEREN CAMINOS SLP ESTIMACION I',                 'fecha_emision' => '2025-02-15', 'fecha_vencimiento' => '2025-02-15', 'importe' => '885985.49',  'retencion_pct' => '0.00'],
            ['contrato_id' => 8, 'folio' => 'A5017', 'tipo' => 'finiquito',  'numero_estimacion' => 2,    'concepto' => 'PEREN CAMINOS SLP ESTIMACION II FINIQUITO',      'fecha_emision' => '2025-03-14', 'fecha_vencimiento' => '2025-03-14', 'importe' => '488844.45',  'retencion_pct' => '0.00'],
            ['contrato_id' => 6, 'folio' => 'A5020', 'tipo' => 'otro',       'numero_estimacion' => null, 'concepto' => 'VIAJES',                                         'fecha_emision' => '2025-03-21', 'fecha_vencimiento' => '2025-04-21', 'importe' => '300000.00',  'retencion_pct' => '0.00'],
            ['contrato_id' => 2, 'folio' => 'A5018', 'tipo' => 'otro',       'numero_estimacion' => null, 'concepto' => 'ADENDA PAVIMENTACION CAMINO DE ACCESO',          'fecha_emision' => '2025-03-27', 'fecha_vencimiento' => '2025-03-28', 'importe' => '5156651.00', 'retencion_pct' => '0.00'],
        ]);

        $cuentaBanco = (int) DB::table('cuentas')->where('nombre', 'Banco del Bajío (GADIT)')->value('id');
        $folio = fn (string $f) => DB::table('facturas')->where('folio', $f)->value('id');

        DB::table('cobros')->insert([
            ['factura_id' => $folio('A5001'), 'cuenta_id' => $cuentaBanco, 'fecha' => '2024-11-14', 'importe' => '5029866.82', 'iva' => '804778.69'],
            ['factura_id' => $folio('A5005'), 'cuenta_id' => $cuentaBanco, 'fecha' => '2024-11-27', 'importe' => '2176976.85', 'iva' => '348316.30'],
            ['factura_id' => $folio('A5008'), 'cuenta_id' => $cuentaBanco, 'fecha' => '2024-11-27', 'importe' => '448439.32',  'iva' => '71750.29'],
            ['factura_id' => $folio('A5006'), 'cuenta_id' => $cuentaBanco, 'fecha' => '2024-12-09', 'importe' => '892776.90',  'iva' => '142844.30'],
            ['factura_id' => $folio('A5010'), 'cuenta_id' => $cuentaBanco, 'fecha' => '2024-12-10', 'importe' => '700044.00',  'iva' => '112007.04'],
            ['factura_id' => $folio('A5014'), 'cuenta_id' => $cuentaBanco, 'fecha' => '2024-12-12', 'importe' => '738321.24',  'iva' => '118131.40'],
            ['factura_id' => $folio('A5012'), 'cuenta_id' => $cuentaBanco, 'fecha' => '2024-12-17', 'importe' => '154700.00',  'iva' => '24752.00'],
            ['factura_id' => $folio('A5007'), 'cuenta_id' => $cuentaBanco, 'fecha' => '2024-12-19', 'importe' => '116680.00',  'iva' => '18668.80'],
            ['factura_id' => $folio('A5016'), 'cuenta_id' => $cuentaBanco, 'fecha' => '2025-02-15', 'importe' => '885985.49',  'iva' => '141757.68'],
            ['factura_id' => $folio('A5017'), 'cuenta_id' => $cuentaBanco, 'fecha' => '2025-03-14', 'importe' => '488844.45',  'iva' => '78215.11'],
            ['factura_id' => $folio('A5018'), 'cuenta_id' => $cuentaBanco, 'fecha' => '2025-03-28', 'importe' => '5156651.00', 'iva' => '825064.16'],
        ]);
    }
}
