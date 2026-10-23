<?php

namespace App\Models;

use App\Enums\RolUsuario;
use App\Models\Concerns\Auditable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    use Auditable;

    protected $table = 'usuarios';

    protected $fillable = ['nombre', 'email', 'password', 'rol', 'activo'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'rol'      => RolUsuario::class,
            'activo'   => 'boolean',
        ];
    }

    // Sin "recuérdame" (SEG-03)
    public function getRememberTokenName(): string
    {
        return '';
    }
}
