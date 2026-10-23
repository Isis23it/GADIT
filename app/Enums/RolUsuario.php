<?php

namespace App\Enums;

enum RolUsuario: string
{
    case Admin    = 'admin';
    case Captura  = 'captura';
    case Consulta = 'consulta';

    public function label(): string
    {
        return match ($this) {
            self::Admin    => 'Administrador',
            self::Captura  => 'Captura',
            self::Consulta => 'Consulta',
        };
    }
}
