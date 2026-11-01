<?php

namespace Database\Factories;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    /**
     * Contraseña con hash que se reutiliza entre registros (evita recalcularlo cada vez).
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'rol' => RolUsuario::Captura,
            'activo' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $atributos) => ['rol' => RolUsuario::Admin]);
    }

    public function consulta(): static
    {
        return $this->state(fn (array $atributos) => ['rol' => RolUsuario::Consulta]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $atributos) => ['activo' => false]);
    }
}
