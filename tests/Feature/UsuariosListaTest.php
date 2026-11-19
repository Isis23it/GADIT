<?php

use App\Enums\RolUsuario;
use App\Livewire\Usuarios\Usuarios;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('RF-15: un invitado que pide /usuarios va al inicio de sesión', function () {
    $this->get('/usuarios')->assertRedirect(route('login'));
});

it('RF-15: el administrador ve nombre, correo, rol, estado y fecha de alta', function () {
    $admin = Usuario::factory()->admin()->create(['nombre' => 'Ana Admin', 'created_at' => '2026-10-14 10:00:00']);
    Usuario::factory()->create(['nombre' => 'Beto Captura', 'email' => 'beto@gadit.test', 'rol' => RolUsuario::Captura]);
    Usuario::factory()->consulta()->inactivo()->create(['nombre' => 'Carla Consulta']);

    $this->actingAs($admin)->get('/usuarios')
        ->assertOk()
        ->assertSee('Ana Admin')
        ->assertSee('Administrador')
        ->assertSee('beto@gadit.test')
        ->assertSee('Captura')
        ->assertSee('Consulta')
        ->assertSee('Activo')
        ->assertSee('Inactivo')
        ->assertSee('14/10/2026')
        ->assertSee('3 registros');
});

it('RF-15: los usuarios van ordenados por nombre', function () {
    $admin = Usuario::factory()->admin()->create(['nombre' => 'Zoe Admin']);
    Usuario::factory()->create(['nombre' => 'Ana Captura']);

    Livewire::actingAs($admin)->test(Usuarios::class)->assertSeeInOrder(['Ana Captura', 'Zoe Admin']);
});

it('RF-82: con un solo usuario el conteo dice «1 registro»', function () {
    $admin = Usuario::factory()->admin()->create();

    $this->actingAs($admin)->get('/usuarios')->assertOk()->assertSee('1 registro');
});

it('RF-15: captura y consulta reciben 403 en /usuarios', function (RolUsuario $rol) {
    $usuario = Usuario::factory()->create(['rol' => $rol]);

    $this->actingAs($usuario)->get('/usuarios')->assertForbidden();
})->with([RolUsuario::Captura, RolUsuario::Consulta]);
