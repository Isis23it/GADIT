<?php

namespace App\Actions;

use App\Exceptions\ReglaDeNegocioException;
use Illuminate\Database\Eloquent\Model;

abstract class Action
{
    protected function verificarBloqueoOptimista(Model $model, string $updatedAt): void
    {
        $actual = (string) $model->fresh()?->getAttribute('updated_at');

        if ($actual !== $updatedAt) {
            throw new ReglaDeNegocioException(
                'Otra persona modificó este registro mientras lo editabas. Recarga para ver los cambios'
            );
        }
    }
}
