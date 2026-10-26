<?php

use App\Livewire\Auth\IniciarSesion;
use App\Livewire\Auth\OlvideMiContrasena;
use App\Livewire\Auth\RestablecerContrasena;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Autenticación (solo invitados)
Route::middleware('guest')->group(function () {
    Route::livewire('/iniciar-sesion', IniciarSesion::class)->name('login');
    Route::livewire('/olvide-mi-contrasena', OlvideMiContrasena::class)->name('password.request');
    Route::livewire('/restablecer-contrasena/{token}', RestablecerContrasena::class)->name('password.reset');
});

// Cierre de sesión (RF-12)
Route::post('/cerrar-sesion', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Raíz redirige al tablero (o al login si no está autenticado)
Route::get('/', fn () => redirect()->route('tablero'))->middleware('auth');
