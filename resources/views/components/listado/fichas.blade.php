@props(['fichas' => []])

@if ($fichas !== [])
    <ul class="mt-3 flex flex-wrap items-center gap-2" aria-label="Filtros activos">
        @foreach ($fichas as $clave => $texto)
            <li wire:key="ficha-{{ $clave }}" class="inline-flex min-h-11 items-center rounded-full bg-gray-100 pl-3 text-sm text-gray-800">
                <span>{{ $texto }}</span>
                <button type="button" wire:click="quitarFiltro('{{ $clave }}')" aria-label="Quitar filtro {{ $texto }}"
                        class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-full text-gray-600 hover:text-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">
                    <span aria-hidden="true">✕</span>
                </button>
            </li>
        @endforeach
        <li>
            <button type="button" wire:click="limpiarFiltros"
                    class="inline-flex min-h-11 items-center px-2 text-sm text-blue-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">
                Limpiar filtros
            </button>
        </li>
    </ul>
@endif
