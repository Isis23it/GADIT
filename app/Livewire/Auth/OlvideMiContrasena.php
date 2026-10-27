<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class OlvideMiContrasena extends Component
{
    public string $email = '';

    public bool $enviado = false;

    public function enviar(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        // Siempre responde igual para no revelar si el correo existe (RF-13)
        Password::sendResetLink(['email' => $this->email]);

        $this->enviado = true;
    }

    public function render(): View
    {
        return view('livewire.auth.olvide-mi-contrasena');
    }
}
