<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogosBaseSeeder extends Seeder
{
    private const CATEGORIAS = [
        'obra' => ['CONCRETO', 'MAQUINARIA', 'MATERIALES DE OBRA', 'FERRETERIA', 'LABORATORIO', 'TOPOGRAFIA', 'AGUA Y PIPAS', 'FLETES Y REMOLQUES', 'EQUIPO DE SEGURIDAD', 'SERVICIOS DE OBRA'],
        'nomina' => ['NOMINA', 'FINIQUITOS'],
        'impuestos' => ['SAT', 'IMSS', 'ISR', 'ISN', 'IVA'],
        'comisiones' => ['COMISIONES', 'COMISION INTERMEDIARIO'],
        'reparto_utilidad' => ['UTILIDAD'],
        'financiamiento' => ['INTERESES', 'TRAMITES Y COMISIONES DE PRESTAMO'],
        'logistica' => ['COMBUSTIBLE', 'CASETAS', 'VEHICULOS', 'VIATICOS'],
        'administracion' => ['ADMINISTRACION', 'SISTEMAS Y SERVICIOS', 'FIANZAS Y SEGUROS', 'RENTAS'],
        'otros' => ['APOYOS', 'VARIOS'],
    ];

    private const PERSONAS = [
        ['nombre' => 'Alejandra', 'es_socio' => 1],
        ['nombre' => 'Javier', 'es_socio' => 1],
        ['nombre' => 'Álvaro', 'es_socio' => 1],
        ['nombre' => 'GPE', 'es_socio' => 1],
        ['nombre' => 'Alfredo', 'es_socio' => 0],
    ];

    private const CUENTAS = [
        ['nombre' => 'Banco del Bajío (GADIT)', 'tipo' => 'banco', 'persona' => null, 'notas' => 'Supuesto de la carga inicial: todos los cobros entraron aquí'],
        ['nombre' => 'Caja Alejandra', 'tipo' => 'caja', 'persona' => 'Alejandra', 'notas' => null],
        ['nombre' => 'Alfredo (intermediario)', 'tipo' => 'intermediario', 'persona' => 'Alfredo', 'notas' => null],
        ['nombre' => 'Caja GPE', 'tipo' => 'caja', 'persona' => 'GPE', 'notas' => null],
        ['nombre' => 'Caja Javier', 'tipo' => 'caja', 'persona' => 'Javier', 'notas' => null],
    ];

    private const ESQUEMA = 'Reparto general (CONCENTRADO)';

    private const REPARTO = [
        'Alejandra' => '30.00',
        'Javier' => '25.00',
        'Álvaro' => '22.50',
        'GPE' => '22.50',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->categorias();
            $this->personas();
            $this->cuentas();
            $this->esquemaDeReparto();
        });
    }

    private function categorias(): void
    {
        foreach (self::CATEGORIAS as $grupo => $nombres) {
            foreach ($nombres as $nombre) {
                $this->crearSiNoExiste('categorias', $nombre, ['grupo' => $grupo]);
            }
        }
    }

    private function personas(): void
    {
        foreach (self::PERSONAS as $persona) {
            $this->crearSiNoExiste('personas', $persona['nombre'], ['es_socio' => $persona['es_socio']]);
        }
    }

    private function cuentas(): void
    {
        foreach (self::CUENTAS as $cuenta) {
            $this->crearSiNoExiste('cuentas', $cuenta['nombre'], [
                'tipo' => $cuenta['tipo'],
                'persona_id' => $cuenta['persona'] ? $this->idPersona($cuenta['persona']) : null,
                'notas' => $cuenta['notas'],
            ]);
        }
    }

    private function esquemaDeReparto(): void
    {
        // Solo se siembra si no existe, para no deshacer lo que el dueño cambie en RF-54.
        if (DB::table('esquemas_reparto')->where('nombre', self::ESQUEMA)->exists()) {
            return;
        }

        $esquemaId = DB::table('esquemas_reparto')->insertGetId([
            'nombre' => self::ESQUEMA,
            'notas' => 'Valores iniciales del Excel: 30 / 25 / 22.5 / 22.5',
        ]);

        foreach (self::REPARTO as $persona => $porcentaje) {
            DB::table('esquema_reparto_socios')->insert([
                'esquema_id' => $esquemaId,
                'persona_id' => $this->idPersona($persona),
                'porcentaje' => $porcentaje,
            ]);
        }
    }

    /** @param array<string, mixed> $datos */
    private function crearSiNoExiste(string $tabla, string $nombre, array $datos): void
    {
        if (! DB::table($tabla)->where('nombre', $nombre)->exists()) {
            DB::table($tabla)->insert(['nombre' => $nombre] + $datos);
        }
    }

    private function idPersona(string $nombre): int
    {
        return (int) DB::table('personas')->where('nombre', $nombre)->value('id');
    }
}
