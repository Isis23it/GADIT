<?php

use App\Http\Controllers\DescargaAdjuntoController;
use App\Livewire\Auth\IniciarSesion;
use App\Livewire\Auth\MiPerfil;
use App\Livewire\Auth\OlvideMiContrasena;
use App\Livewire\Auth\RestablecerContrasena;
use App\Livewire\Tablero\Tablero;
use App\Livewire\Usuarios\Usuarios;
use App\Models\Usuario;
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

// Pantallas internas (requieren sesión)
Route::middleware('auth')->group(function () {
    Route::livewire('/tablero', Tablero::class)->name('tablero');
    Route::livewire('/perfil', MiPerfil::class)->name('perfil');
    Route::livewire('/usuarios', Usuarios::class)->name('usuarios')->middleware('can:viewAny,'.Usuario::class);

    // RF-34 / SEG-08: adjuntos privados servidos por controlador con política del padre
    Route::get('/adjuntos/{adjunto}', DescargaAdjuntoController::class)->name('adjuntos.descargar');
});
