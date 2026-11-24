<?php

namespace Database\Factories;

use App\Models\Adjunto;
use App\Models\Gasto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Adjunto> */
class AdjuntoFactory extends Factory
{
    protected $model = Adjunto::class;

    public function definition(): array
    {
        $uuid = Str::uuid();

        return [
            'entidad'    => 'gasto',
            'entidad_id' => fn () => Gasto::factory()->create()->id,
            'nombre'     => 'documento.pdf',
            'ruta'       => "adjuntos/gasto/0/{$uuid}.pdf",
            'mime'       => 'application/pdf',
            'subido_por' => null,
        ];
    }
}
