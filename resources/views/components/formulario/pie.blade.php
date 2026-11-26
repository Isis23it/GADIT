@props(['formulario' => null, 'otro' => false])

{{--
    RF-04: los botones se desactivan mientras se guarda (evita doble envío).
    Dentro de <x-modal> el pie queda fuera del <form>: pasar formulario="id-del-form".
    El componente Livewire define guardar() y, si hay «capturar otro», guardarYCapturarOtro().
--}}
<button
    type="submit"
    @if ($formulario) form="{{ $formulario }}" @endif
    wire:loading.attr="disabled"
    wire:target="guardar,guardarYCapturarOtro"
    class="min-h-11 rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-50"
>Guardar</button>

@if ($otro)
    <button
        type="button"
        wire:click="guardarYCapturarOtro"
        wire:loading.attr="disabled"
        wire:target="guardar,guardarYCapturarOtro"
        class="min-h-11 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50 disabled:opacity-50"
    >Guardar y capturar otro</button>
@endif
