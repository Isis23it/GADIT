<?php

namespace App\Actions;

use App\Exceptions\ReglaDeNegocioException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class Action
{
    protected function verificarBloqueoOptimista(Model $model, string $updatedAt): void
    {
        // null si el registro fue borrado lógicamente (soft delete scope); el conflicto reportado es aceptable (DEC-12)
        $actual = (string) $model->newQuery()->lockForUpdate()->find($model->getKey())?->getAttribute('updated_at');

        if ($actual !== $updatedAt) {
            throw new ReglaDeNegocioException(
                'Otra persona modificó este registro mientras lo editabas. Recarga para ver los cambios'
            );
        }
    }

    /**
     * @template TReturn
     *
     * @param  \Closure(): TReturn  $fn
     * @return TReturn
     */
    protected function transaccion(\Closure $fn): mixed
    {
        return DB::transaction($fn);
    }
}
