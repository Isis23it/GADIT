<?php

namespace App\Livewire\Auth;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Mi perfil'])]
class MiPerfil extends Component
{
    private const MENSAJES = [
        'nombre.required' => 'Escribe tu nombre.',
        'password_actual.required' => 'Escribe tu contraseña actual.',
        'password_actual.current_password' => 'La contraseña actual no es correcta.',
        'password.required' => 'Escribe la nueva contraseña.',
        'password.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
    ];

    public string $nombre = '';

    public string $password_actual = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->nombre = $this->usuario()->nombre;
    }

    public function guardarNombre(): void
    {
        $this->nombre = trim($this->nombre);

        $datos = $this->validate(['nombre' => ['required', 'string', 'max:100']], self::MENSAJES);

        // Solo el nombre: el correo y el rol los cambia un administrador (RF-14).
        $this->usuario()->update(['nombre' => $datos['nombre']]);

        $this->dispatch('aviso', tipo: 'exito', mensaje: 'Nombre actualizado.');
    }

    public function cambiarContrasena(): void
    {
        $this->validate([
            'password_actual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)->uncompromised()],
        ], self::MENSAJES);

        $this->usuario()->update(['password' => $this->password]);

        $this->reset('password_actual', 'password', 'password_confirmation');
        $this->dispatch('aviso', tipo: 'exito', mensaje: 'Contraseña actualizada.');
    }

    private function usuario(): Usuario
    {
        $usuario = Auth::user();
        abort_unless($usuario instanceof Usuario, 403);

        return $usuario;
    }

    public function render(): View
    {
        return view('livewire.auth.mi-perfil', ['usuario' => $this->usuario()]);
    }
}
