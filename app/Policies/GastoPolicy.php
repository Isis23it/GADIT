<?php

namespace App\Policies;

use App\Models\Gasto;
use App\Models\Usuario;

class GastoPolicy
{
    // Todos los roles ven gastos (sección 6: L C B)
    public function view(Usuario $user, Gasto $gasto): bool
    {
        return true;
    }
}
