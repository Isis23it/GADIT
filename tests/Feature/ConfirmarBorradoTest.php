<?php

it('RF-06: la confirmación pregunta por el registro y muestra su resumen', function () {
    $this->blade('<x-confirmar-borrado nombre="borrar" registro="Gasto #12">Gasolina · $1,234.56</x-confirmar-borrado>')
        ->assertSee('¿Eliminar Gasto #12?')
        ->assertSee('Gasolina · $1,234.56')
        ->assertSee('Cancelar')
        ->assertSee('Eliminar');
});

it('RF-83: el botón Eliminar es de peligro en rojo', function () {
    $this->blade('<x-confirmar-borrado nombre="borrar" registro="Gasto #12">Resumen</x-confirmar-borrado>')
        ->assertSee('bg-red-700', false);
});

it('RF-06: el motivo solo aparece cuando se pide', function () {
    $this->blade('<x-confirmar-borrado nombre="borrar" registro="Gasto #12">Resumen</x-confirmar-borrado>')
        ->assertDontSee('Motivo (opcional)');

    $this->blade('<x-confirmar-borrado nombre="borrar" registro="Gasto #12" :motivo="true">Resumen</x-confirmar-borrado>')
        ->assertSee('Motivo (opcional)')
        ->assertSee('<textarea', false);
});

it('RF-06: la acción de Livewire llega al botón Eliminar', function () {
    $this->blade('<x-confirmar-borrado nombre="borrar" registro="Gasto #12" wire:click="eliminar">Resumen</x-confirmar-borrado>')
        ->assertSee('wire:click="eliminar"', false);
});
