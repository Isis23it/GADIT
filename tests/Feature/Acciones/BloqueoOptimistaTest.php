<?php

use App\Actions\Action;
use App\Exceptions\ReglaDeNegocioException;
use App\Models\Gasto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

it('DEC-12: lanza ReglaDeNegocioException cuando updated_at cambió', function () {
    $gasto = Gasto::factory()->create();
    $updatedAt = (string) $gasto->updated_at;

    DB::table('gastos')->where('id', $gasto->id)->update(['updated_at' => now()->addSecond()]);

    $accion = new class extends Action
    {
        public function verificar(Model $model, string $updatedAt): void
        {
            $this->verificarBloqueoOptimista($model, $updatedAt);
        }
    };

    expect(fn () => $accion->verificar($gasto, $updatedAt))
        ->toThrow(
            ReglaDeNegocioException::class,
            'Otra persona modificó este registro mientras lo editabas. Recarga para ver los cambios'
        );
});

it('DEC-12: no lanza excepción cuando updated_at no cambió', function () {
    $gasto = Gasto::factory()->create();
    $updatedAt = (string) $gasto->updated_at;

    $accion = new class extends Action
    {
        public function verificar(Model $model, string $updatedAt): void
        {
            $this->verificarBloqueoOptimista($model, $updatedAt);
        }
    };

    $accion->verificar($gasto, $updatedAt);

    expect(true)->toBeTrue();
});
