<?php

namespace Database\Factories;

use App\Models\Gasto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/** @extends Factory<Gasto> */
class GastoFactory extends Factory
{
    protected $model = Gasto::class;

    public function definition(): array
    {
        $categoriaId = DB::table('categorias')->insertGetId([
            'nombre' => 'TEST-'.fake()->unique()->uuid(),
            'grupo' => 'otros',
        ]);

        $cuentaId = DB::table('cuentas')->insertGetId([
            'nombre' => 'TEST-'.fake()->unique()->uuid(),
            'tipo' => 'caja',
        ]);

        return [
            'fecha' => '2024-01-15',
            'concepto' => 'Gasto de prueba',
            'importe_con_iva' => '100.00',
            'categoria_id' => $categoriaId,
            'cuenta_id' => $cuentaId,
        ];
    }
}
