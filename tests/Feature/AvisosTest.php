<?php

it('RF-04: el aviso de éxito se cierra solo a los 4 segundos', function () {
    $this->blade('<x-avisos />')
        ->assertSee('setTimeout(() => this.quitar(aviso.id), 4000)', false)
        ->assertSee("aviso.tipo === 'exito'", false);
});

it('RF-83: cada tipo de aviso usa su color de la paleta', function () {
    $this->blade('<x-avisos />')
        ->assertSee('bg-green-100 text-green-800', false)
        ->assertSee('bg-amber-100 text-amber-800', false)
        ->assertSee('bg-red-100 text-red-800', false);
});

it('RF-85: el éxito usa role status y los demás role alert, dentro de una región aria-live', function () {
    $this->blade('<x-avisos />')
        ->assertSee("aviso.tipo === 'exito' ? 'status' : 'alert'", false)
        ->assertSee('aria-live="polite"', false);
});

it('RF-83: se ubica arriba a todo el ancho en móvil y a la derecha en escritorio', function () {
    $this->blade('<x-avisos />')
        ->assertSee('inset-x-0 top-0', false)
        ->assertSee('sm:right-0', false);
});
