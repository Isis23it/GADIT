<?php

namespace App\Livewire\Auth;

use App\Models\Usuario;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class RestablecerContrasena extends Component
{
    public string $token                 = '';
    public string $email                 = '';
    public string $password              = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email')->value();
    }

    public function restablecer(): void
    {
        $this->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->uncompromised()],
        ]);

        $estado = Password::reset(
            [
                'email'                 => $this->email,
                'password'              => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token'                 => $this->token,
            ],
            fn (Usuario $usuario, string $password) => $usuario->forceFill(['password' => $password])->save(),
        );

        if ($estado !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($estado)]);
        }

        $this->redirect(route('login'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.auth.restablecer-contrasena');
    }
}
