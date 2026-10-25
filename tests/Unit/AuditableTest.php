<?php

use App\Models\Concerns\Auditable;

it('filtrarAtributos excluye campos sensibles', function () {
    $wrapper = new class
    {
        use Auditable;

        public function filtrar(array $attrs, array $excluidos): array
        {
            return self::filtrarAtributos($attrs, $excluidos);
        }
    };

    $excluidos = ['password', 'remember_token', 'clabe', 'updated_at'];

    expect(
        $wrapper->filtrar(
            ['nombre' => 'foo', 'password' => 'secret', 'clabe' => '123456789012345678', 'updated_at' => '2026-01-01', 'nota' => 'ok'],
            $excluidos,
        )
    )->toBe(['nombre' => 'foo', 'nota' => 'ok']);
});

it('filtrarAtributos devuelve arreglo vacío si todos son excluidos', function () {
    $wrapper = new class
    {
        use Auditable;

        public function filtrar(array $attrs, array $excluidos): array
        {
            return self::filtrarAtributos($attrs, $excluidos);
        }
    };

    expect(
        $wrapper->filtrar(['password' => 'x', 'updated_at' => 'y'], ['password', 'updated_at'])
    )->toBe([]);
});
