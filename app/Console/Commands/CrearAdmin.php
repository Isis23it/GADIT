<?php

namespace App\Console\Commands;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/** Crea el primer usuario administrador real (TDD sección 19, E-18). */
class CrearAdmin extends Command
{
    protected $signature = 'gadit:crear-admin';

    protected $description = 'Crea un usuario administrador para producción';

    public function handle(): int
    {
        $nombre = trim($this->ask('Nombre'));

        if ($nombre === '') {
            $this->error('El nombre no puede estar vacío.');

            return self::FAILURE;
        }

        $email = $this->ask('Correo electrónico');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('El correo electrónico no es válido.');

            return self::FAILURE;
        }

        if (Usuario::where('email', $email)->exists()) {
            $this->error("Ya existe un usuario con el correo «{$email}».");

            return self::FAILURE;
        }

        $password = $this->secret('Contraseña (mínimo 12 caracteres)');

        if (mb_strlen($password) < 12) {
            $this->error('La contraseña debe tener al menos 12 caracteres (SEG-01).');

            return self::FAILURE;
        }

        $confirmacion = $this->secret('Confirmar contraseña');

        if ($password !== $confirmacion) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        $validator = Validator::make(['password' => $password], [
            'password' => [Password::min(12)->uncompromised()],
        ]);

        if ($validator->fails()) {
            $this->error('La contraseña está en una lista de contraseñas filtradas (SEG-01). Elige otra.');

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
