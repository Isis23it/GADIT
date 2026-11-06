<?php

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Support\Facades\Gate;

// ── Middleware usuario.activo ─────────────────────────────────────────────────

it('sección 6: un usuario con activo = 0 pierde la sesión en su siguiente petición', function () {
    $inactivo = new Usuario(['nombre' => 'Test', 'activo' => false, 'rol' => RolUsuario::Captura]);

    $this->actingAs($inactivo)->get('/')->assertRedirect(route('login'));
});

// ── Políticas: una prueba por entrada activa de la matriz ─────────────────────

it('sección 6: la política permite y deniega según la matriz', function (array $entrada) {
    $this->actingAs(new Usuario(['rol' => $entrada['rol'], 'activo' => true]));
    $instancia = new ($entrada['modelo'])();

    $sinInstancia = ['viewAny', 'create'];

    foreach ($entrada['puede'] as $accion) {
        $args = in_array($accion, $sinInstancia) ? $entrada['modelo'] : $instancia;
        expect(Gate::allows($accion, $args))
            ->toBeTrue("{$entrada['modulo']} / {$entrada['rol']->value} / $accion debería estar permitida");
    }

    foreach ($entrada['no_puede'] as $accion) {
        $args = in_array($accion, $sinInstancia) ? $entrada['modelo'] : $instancia;
        expect(Gate::denies($accion, $args))
            ->toBeTrue("{$entrada['modulo']} / {$entrada['rol']->value} / $accion debería estar denegada");
    }
})->with(function () {
    return collect(require __DIR__.'/matriz.php')
        ->filter(fn ($e) => $e['modelo'] !== null)
        ->map(fn ($e) => ["{$e['modulo']}/{$e['rol']->value}" => [$e]])
        ->collapse()
        ->all();
});
