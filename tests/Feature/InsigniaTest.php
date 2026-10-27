<?php

it('RF-83: la insignia verde usa texto verde sobre fondo verde claro', function () {
    $this->blade('<x-insignia color="verde">Cobrada</x-insignia>')
        ->assertSee('Cobrada')
        ->assertSee('bg-green-100 text-green-800', false);
});

it('RF-83: cada color de la paleta tiene su combinación', function (string $color, string $clases) {
    $this->blade("<x-insignia color=\"{$color}\">Estado</x-insignia>")
        ->assertSee($clases, false);
})->with([
    ['verde', 'bg-green-100 text-green-800'],
    ['ambar', 'bg-amber-100 text-amber-800'],
    ['rojo', 'bg-red-100 text-red-800'],
    ['azul', 'bg-blue-100 text-blue-800'],
    ['gris', 'bg-gray-100 text-gray-700'],
]);

it('RF-83: un color desconocido falla en vez de pintarse sin estilo', function () {
    expect(fn () => $this->blade('<x-insignia color="morado">Estado</x-insignia>')->assertSee('Estado'))
        ->toThrow(Exception::class);
});
