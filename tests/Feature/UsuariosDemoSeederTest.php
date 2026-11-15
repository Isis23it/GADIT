<?php

use Database\Seeders\UsuariosDemoSeeder;

it('E-18: UsuariosDemoSeeder lanza excepción en producción', function () {
    $this->app->detectEnvironment(fn () => 'production');

    expect(fn () => (new UsuariosDemoSeeder)->run())
        ->toThrow(RuntimeException::class);
});

it('E-18: UsuariosDemoSeeder es idempotente (se puede correr dos veces)', function () {
    $this->seed(UsuariosDemoSeeder::class);
    $this->seed(UsuariosDemoSeeder::class);

    expect(true)->toBeTrue();
});
