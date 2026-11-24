@props(['plural', 'filtrado' => false])

<div class="mt-3 rounded-lg border border-dashed border-gray-300 bg-white px-4 py-10 text-center">
    @if ($filtrado)
        <p class="text-sm text-gray-700">No hay registros con estos filtros</p>
        <button type="button" wire:click="limpiarFiltros"
                class="mt-4 inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">
            Limpiar filtros
        </button>
    @else
        <p class="text-sm text-gray-700">Aún no hay {{ $plural }}</p>
        @isset($accion)
            <div class="mt-4">{{ $accion }}</div>
        @endisset
    @endif
</div>
