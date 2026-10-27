<?php

use Illuminate\Support\Facades\Route;

it('RF-10: una ruta que no existe muestra la página 404 amable', function () {
    $this->withoutVite();

    $this->get('/ruta-que-no-existe')
        ->assertNotFound()
        ->assertSee('No encontramos lo que buscas')
        ->assertSee('Ir al tablero');
});

it('RF-10: un permiso denegado muestra la página 403 amable', function () {
    $this->withoutVite();
    Route::get('/prueba-403', fn () => abort(403));

    $this->get('/prueba-403')
        ->assertForbidden()
        ->assertSee('No tienes permiso para ver esto')
        ->assertSee('Ir al tablero');
});
