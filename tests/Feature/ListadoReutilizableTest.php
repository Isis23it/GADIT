<?php

use App\Livewire\Usuarios\Usuarios;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function adminDeListado(): Usuario
{
    return Usuario::factory()->admin()->create(['nombre' => 'Mario Admin', 'email' => 'm@gadit.test']);
}

it('RF-01: por defecto lista 25 por página y ofrece 10, 25, 50 y 100', function () {
    Usuario::factory()->count(30)->create();

    Livewire::actingAs(adminDeListado())->test(Usuarios::class)
        ->assertViewHas('usuarios', fn ($p) => $p->perPage() === 25 && $p->total() === 31)
        ->assertSeeHtml('<option value="10">10</option>')
        ->assertSeeHtml('<option value="25">25</option>')
        ->assertSeeHtml('<option value="50">50</option>')
        ->assertSeeHtml('<option value="100">100</option>');
});

it('RF-01: el selector cambia el tamaño de página y un valor ajeno vuelve a 25', function () {
    Usuario::factory()->count(30)->create();

    Livewire::actingAs(adminDeListado())->test(Usuarios::class)
        ->set('porPagina', 10)
        ->assertViewHas('usuarios', fn ($p) => $p->perPage() === 10)
        ->assertSee('Página 1 de 4')
        ->set('porPagina', 7)
        ->assertViewHas('usuarios', fn ($p) => $p->perPage() === 25);
});

it('RF-01: se ordena por el encabezado, alterna asc/desc y marca aria-sort', function () {
    $admin = adminDeListado();
    Usuario::factory()->create(['email' => 'a@gadit.test']);
    Usuario::factory()->create(['email' => 'z@gadit.test']);

    Livewire::actingAs($admin)->test(Usuarios::class)
        ->assertSeeHtml('aria-sort="none"')
        ->call('ordenarPor', 'correo')
        ->assertSet('orden', 'correo')
        ->assertSet('direccion', 'asc')
        ->assertSeeHtml('aria-sort="ascending"')
        ->assertSeeInOrder(['a@gadit.test', 'm@gadit.test', 'z@gadit.test'])
        ->call('ordenarPor', 'correo')
        ->assertSet('direccion', 'desc')
        ->assertSeeHtml('aria-sort="descending"')
        ->assertSeeInOrder(['z@gadit.test', 'm@gadit.test', 'a@gadit.test']);
});

it('RF-01: una columna de orden inventada en la URL no llega a la consulta', function () {
    Livewire::actingAs(adminDeListado())->test(Usuarios::class)
        ->set('orden', 'nombre; DROP TABLE usuarios')
        ->assertOk()
        ->assertSee('Mario Admin')
        ->assertViewHas('ordenActivo', 'nombre');

    expect(Usuario::count())->toBe(1);
});

it('RF-01: la búsqueda espera 300 ms y filtra por nombre o correo', function () {
    $admin = adminDeListado();
    Usuario::factory()->create(['nombre' => 'Beto Captura', 'email' => 'beto@gadit.test']);
    Usuario::factory()->create(['nombre' => 'Ana Consulta', 'email' => 'ana@gadit.test']);

    Livewire::actingAs($admin)->test(Usuarios::class)
        ->assertSeeHtml('wire:model.live.debounce.300ms="buscar"')
        ->set('buscar', 'beto')
        ->assertSee('Beto Captura')
        ->assertDontSee('Ana Consulta')
        ->assertSee('1 registro')
        ->set('buscar', 'ana@')
        ->assertSee('Ana Consulta')
        ->assertDontSee('Beto Captura');
});

it('RF-01: los comodines de la búsqueda se toman como texto', function () {
    $admin = adminDeListado();
    Usuario::factory()->create(['nombre' => 'Cien % Real']);

    Livewire::actingAs($admin)->test(Usuarios::class)
        ->set('buscar', '%')
        ->assertViewHas('usuarios', fn ($p) => $p->total() === 1)
        ->assertSee('Cien % Real');
});

it('RF-01: búsqueda, orden, página y filtros viven en la URL', function () {
    Livewire::withQueryParams([
        'q' => 'beto',
        'orden' => 'correo',
        'dir' => 'desc',
        'por_pagina' => 50,
        'filtros' => ['rol' => 'consulta'],
    ])->actingAs(adminDeListado())->test(Usuarios::class)
        ->assertSet('buscar', 'beto')
        ->assertSet('orden', 'correo')
        ->assertSet('direccion', 'desc')
        ->assertSet('porPagina', 50)
        ->assertSet('filtros', ['rol' => 'consulta']);
});

it('RF-01: cambiar búsqueda, filtros o tamaño regresa a la página 1', function () {
    Usuario::factory()->count(30)->create();

    Livewire::actingAs(adminDeListado())->test(Usuarios::class)
        ->call('nextPage')
        ->assertViewHas('usuarios', fn ($p) => $p->currentPage() === 2)
        ->assertSee('Página 2 de 2')
        ->set('porPagina', 50)
        ->assertViewHas('usuarios', fn ($p) => $p->currentPage() === 1)
        ->set('buscar', 'a')
        ->assertViewHas('usuarios', fn ($p) => $p->currentPage() === 1);
});

it('RF-01: un filtro muestra su ficha, se quita con la ✕ y un valor ajeno se ignora', function () {
    $admin = adminDeListado();
    Usuario::factory()->consulta()->create(['nombre' => 'Carla Consulta']);

    Livewire::actingAs($admin)->test(Usuarios::class)
        ->set('filtros.rol', 'consulta')
        ->assertSee('Rol: Consulta')
        ->assertSee('Limpiar filtros')
        ->assertSee('Carla Consulta')
        ->assertDontSee('Mario Admin')
        ->assertSee('1 registro')
        ->call('quitarFiltro', 'rol')
        ->assertSet('filtros', [])
        ->assertSee('Mario Admin')
        ->set('filtros.rol', 'hacker')
        ->assertDontSee('Rol: ')
        ->assertViewHas('usuarios', fn ($p) => $p->total() === 2)
        ->set('filtros.rol', '')
        ->assertSet('filtros', []);
});

it('RF-84: sin resultados dice «No hay registros con estos filtros» y limpia con un clic', function () {
    Livewire::actingAs(adminDeListado())->test(Usuarios::class)
        ->set('buscar', 'zzzz')
        ->assertSee('No hay registros con estos filtros')
        ->assertSee('Limpiar filtros')
        ->assertSee('0 registros')
        ->call('limpiarFiltros')
        ->assertSet('buscar', '')
        ->assertSee('Mario Admin');
});

it('RF-84: la lista vacía dice «Aún no hay {plural}» y admite el botón de crear', function () {
    $this->blade('<x-listado.vacio plural="clientes" />')
        ->assertSee('Aún no hay clientes')
        ->assertDontSee('Crear cliente');

    $this->blade('<x-listado.vacio plural="clientes"><x-slot:accion><a href="/x">Crear cliente</a></x-slot:accion></x-listado.vacio>')
        ->assertSee('Aún no hay clientes')
        ->assertSee('Crear cliente');
});

it('RF-01: el resumen cuenta registros, pluraliza y suma importes cuando los hay', function () {
    $this->blade('<x-listado.resumen :total="1" />')->assertSee('1 registro')->assertDontSee('registros');
    $this->blade('<x-listado.resumen :total="1234" />')->assertSee('1,234 registros');
    $this->blade('<x-listado.resumen :total="2" suma="$1,000.00" />')->assertSee('2 registros · Suma: $1,000.00', false);
});

it('RF-81: la lista trae tabla para escritorio y tarjetas para móvil', function () {
    Livewire::actingAs(adminDeListado())->test(Usuarios::class)
        ->assertSeeHtml('<table')
        ->assertSeeHtml('hidden overflow-x-auto')
        ->assertSeeHtml('md:hidden');
});

it('RNF-01: fuera de producción la carga perezosa lanza excepción', function () {
    expect(Model::preventsLazyLoading())->toBeTrue();
});

it('RF-01: en móvil el orden se elige en un selector «Ordenar por» que no existe en escritorio', function () {
    Livewire::actingAs(adminDeListado())->test(Usuarios::class)
        ->assertSeeHtml('for="listado-ordenar"')
        ->assertSeeHtml('class="col-span-4! md:hidden"')
        ->assertSeeHtml('<option value="correo:asc"')
        ->assertSeeHtml('<option value="correo:desc"')
        ->assertSeeHtml('value="nombre:asc" selected');
});

it('RF-01: el selector de móvil ordena igual que el encabezado y marca la opción activa', function () {
    $admin = adminDeListado();
    Usuario::factory()->create(['email' => 'a@gadit.test']);
    Usuario::factory()->create(['email' => 'z@gadit.test']);

    Livewire::actingAs($admin)->test(Usuarios::class)
        ->call('ordenarCon', 'correo:desc')
        ->assertSet('orden', 'correo')
        ->assertSet('direccion', 'desc')
        ->assertSeeHtml('value="correo:desc" selected')
        ->assertSeeInOrder(['z@gadit.test', 'm@gadit.test', 'a@gadit.test']);
});

it('RF-01: el selector de móvil ignora lo que no es una columna con dirección y regresa a la página 1', function () {
    Usuario::factory()->count(30)->create();

    Livewire::actingAs(adminDeListado())->test(Usuarios::class)
        ->call('nextPage')
        ->call('ordenarCon', '')
        ->assertSet('orden', '')
        ->call('ordenarCon', 'correo:de lado')
        ->assertSet('orden', 'correo')
        ->assertSet('direccion', 'asc')
        ->assertViewHas('usuarios', fn ($p) => $p->currentPage() === 1)
        ->call('ordenarCon', 'x; DROP TABLE usuarios:desc')
        ->assertOk()
        ->assertViewHas('ordenActivo', 'nombre');

    expect(Usuario::count())->toBe(31);
});

it('RF-81: en móvil los controles de la barra van en dos columnas y en escritorio en una sola fila', function () {
    Livewire::actingAs(adminDeListado())->test(Usuarios::class)
        ->assertSeeHtml('grid grid-cols-6 gap-3 md:contents');
});
