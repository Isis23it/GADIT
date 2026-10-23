<?php

namespace App\Models\Concerns;

use App\Enums\AccionBitacora;
use App\Models\Bitacora;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($m) => static::registrarBitacora($m, AccionBitacora::Crear));
        static::updated(fn ($m) => static::registrarBitacora($m, AccionBitacora::Editar));
        static::deleted(fn ($m) => static::registrarBitacora($m, AccionBitacora::Eliminar));
    }

    private static function registrarBitacora(mixed $model, AccionBitacora $accion): void
    {
        $excluidos = ['password', 'remember_token', 'clabe', 'updated_at'];
        $dirty     = $model->getDirty();

        [$antes, $despues] = match ($accion) {
            AccionBitacora::Crear    => [
                null,
                static::filtrarAtributos($model->getAttributes(), $excluidos) ?: null,
            ],
            AccionBitacora::Editar   => [
                static::filtrarAtributos(array_intersect_key($model->getOriginal(), $dirty), $excluidos) ?: null,
                static::filtrarAtributos($dirty, $excluidos) ?: null,
            ],
            AccionBitacora::Eliminar => [
                static::filtrarAtributos($model->getAttributes(), $excluidos) ?: null,
                null,
            ],
        };

        // Ignorar si el único cambio fue en campos excluidos (p.ej. solo updated_at)
        if ($accion === AccionBitacora::Editar && $antes === null && $despues === null) {
            return;
        }

        Bitacora::create([
            'usuario_id'  => Auth::id(),  // null en consola (RN-21)
            'tabla'       => $model->getTable(),
            'registro_id' => $model->getKey(),
            'accion'      => $accion,
            'antes'       => $antes,
            'despues'     => $despues,
        ]);
    }

    protected static function filtrarAtributos(array $attrs, array $excluidos): array
    {
        return array_diff_key($attrs, array_flip($excluidos));
    }
}
