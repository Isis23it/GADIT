<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class IniciarSesion extends Component
{
    public string $email = '';

    public string $password = '';

    public function iniciar(): void
    {
        $this->validarLimite();

        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->hitThrottle();

            throw ValidationException::withMessages([
                'email' => 'Correo o contraseña incorrectos.',
            ]);
        }

        $this->clearThrottle();
        request()->session()->regenerate();

        $this->redirect(session()->pull('url.intended', route('tablero')), navigate: true);
    }

    /** @return list<string> */
    private function throttleKeys(): array
    {
        return [
            'login_email:'.Str::lower($this->email),
            'login_ip:'.request()->ip(),
        ];
    }

    private function validarLimite(): void
    {
        foreach ($this->throttleKeys() as $key) {
            if (RateLimiter::tooManyAttempts($key, 5)) {
                event(new Lockout(request()));
                $segundos = RateLimiter::availableIn($key);
                throw ValidationException::withMessages([
                    'email' => "Demasiados intentos. Espera {$segundos} ".($segundos === 1 ? 'segundo' : 'segundos').'.',
                ]);
            }
        }
    }

    private function hitThrottle(): void
    {
        foreach ($this->throttleKeys() as $key) {
            RateLimiter::hit($key, 60);
        }
    }

    private function clearThrottle(): void
    {
        foreach ($this->throttleKeys() as $key) {
            RateLimiter::clear($key);
        }
    }

    public function render(): View
    {
        return view('livewire.auth.iniciar-sesion');
    }
}
