<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Usuario;

/** Usuarios: solo admin (sección 6, DEC-04). before() siempre cortocircuita; los métodos existen para pasar el callable-check del Gate. */
final class UsuarioPolicy
{
    public function before(Usuario $auth): bool
    {
        return $auth->rol === RolUsuario::Admin;
    }

    public function viewAny(): false
    {
        return false;
    }

    public function view(): false
    {
        return false;
    }

    public function create(): false
    {
        return false;
    }

    public function update(): false
    {
        return false;
    }

    public function delete(): false
    {
        return false;
    }

    public function restore(): false
    {
        return false;
    }
}
