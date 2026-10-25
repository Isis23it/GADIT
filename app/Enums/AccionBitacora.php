<?php

namespace App\Enums;

enum AccionBitacora: string
{
    case Crear = 'crear';
    case Editar = 'editar';
    case Eliminar = 'eliminar';

    public function label(): string
    {
        return match ($this) {
            self::Crear => 'Crear',
            self::Editar => 'Editar',
            self::Eliminar => 'Eliminar',
        };
    }
}
