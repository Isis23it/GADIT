<?php

namespace App\Console\Commands;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Console\Command;

/** Crea el primer usuario administrador real (TDD sección 19, E-18). */
class CrearAdmin extends Command
{
    protected $signature = 'gadit:crear-admin';

    protected $description = 'Crea un usuario administrador para producción';

    public function handle(): int
    {
        $nombre = $this->ask('Nombre');
        $email = $this->ask('Correo electrónico');

        if (Usuario::where('email', $email)->exists()) {
            $this->error("Ya existe un usuario con el correo «{$email}».");

            return self::FAILURE;
        }

        $password = $this->secret('Contraseña (mínimo 12 caracteres)');

        if (strlen($password) < 12) {
            $this->error('La contraseña debe tener al menos 12 caracteres (SEG-01).');

            return self::FAILURE;
        }

        Usuario::create([
            'nombre' => $nombre,
            'email' => $email,
            'password' => $password,
            'rol' => RolUsuario::Admin,
            'activo' => true,
        ]);

        $this->info("Administrador «{$nombre}» creado correctamente.");

        return self::SUCCESS;
    }
}
