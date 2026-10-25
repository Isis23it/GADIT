<?php

it('RF-85: el modal declara role dialog, aria-modal y atrapa el foco', function () {
    $this->blade('<x-modal nombre="prueba" titulo="Editar gasto">Contenido</x-modal>')
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('x-trap.noscroll="abierto"', false)
        ->assertSee('Editar gasto')
        ->assertSee('Contenido');
});

it('RF-83: el modal cierra con Esc, con la X y con clic fuera', function () {
    $this->blade('<x-modal nombre="prueba" titulo="Editar gasto">Contenido</x-modal>')
        ->assertSee('x-on:keydown.escape.window="if (abierto) cerrar()"', false)
        ->assertSee('aria-label="Cerrar"', false)
        ->assertSee('x-on:click="cerrar()" aria-hidden="true"', false);
});

it('RF-83: el modal mide 480 o 640 px y ocupa toda la pantalla en móvil', function () {
    $this->blade('<x-modal nombre="prueba" titulo="Editar gasto" ancho="md">Contenido</x-modal>')
        ->assertSee('md:max-w-[480px]', false)
        ->assertSee('h-full w-full', false);

    $this->blade('<x-modal nombre="prueba" titulo="Editar gasto" ancho="lg">Contenido</x-modal>')
        ->assertSee('md:max-w-[640px]', false);
});

it('RF-84: con cambios sin guardar pregunta si se descartan', function () {
    $this->blade('<x-modal nombre="prueba" titulo="Editar gasto" :descartar="true">Contenido</x-modal>')
        ->assertSee('descartar: true', false)
        ->assertSee('¿Descartar los cambios?')
        ->assertSee('Descartar')
        ->assertSee('Seguir editando');
});

it('RF-83: un ancho desconocido falla en vez de mostrarse mal', function () {
    expect(fn () => $this->blade('<x-modal nombre="prueba" titulo="Editar gasto" ancho="xl">Contenido</x-modal>')->assertSee('Contenido'))
        ->toThrow(Exception::class);
});

it('RF-84: Descartar devuelve los campos a su valor original', function () {
    $this->blade('<x-modal nombre="prueba" titulo="Editar gasto" :descartar="true">Contenido</x-modal>')
        ->assertSee('x-on:click="descartarCambios()"', false)
        ->assertSee('campo.defaultValue', false);
});
