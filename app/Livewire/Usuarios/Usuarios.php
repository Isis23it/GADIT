<?php

namespace App\Livewire\Usuarios;

use App\Models\Usuario;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Usuarios (RF-15, solo admin): lista con nombre, correo, rol, estado y fecha de alta.
 * El alta, el cambio de rol y desactivar/reactivar (RN-23) se conectan con las actions de usuarios de GAD-37.
 */
#[Layout('layouts.app', ['title' => 'Usuarios'])]
class Usuarios extends Component
{
    public function render(): View
    {
        return view('livewire.usuarios.usuarios', [
            'usuarios' => Usuario::query()->orderBy('nombre')->get(),
        ]);
    }
}
