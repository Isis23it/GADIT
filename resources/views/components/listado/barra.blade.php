@props(['etiqueta' => 'Buscar', 'tamanos' => [10, 25, 50, 100], 'ordenes' => [], 'ordenActivo' => '', 'direccion' => 'asc'])

<div {{ $attributes->class(['flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end']) }}>
    <div class="md:w-80">
        <label for="listado-buscar" class="block text-sm font-medium text-gray-700">{{ $etiqueta }}</label>
        <input id="listado-buscar" type="search" wire:model.live.debounce.300ms="buscar" autocomplete="off"
               class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 px-3 text-base shadow-sm focus:border-blue-700 focus:ring-2 focus:ring-blue-700 sm:text-sm">
    </div>

    {{-- En móvil los controles van en dos columnas; desde 768 px vuelven a una sola fila (md:contents) --}}
    <div class="grid grid-cols-2 gap-3 md:contents">
        {{ $slot }}

        @if ($ordenes !== [])
            {{-- Las tarjetas de móvil no tienen encabezados, así que el orden se elige aquí (RF-01) --}}
            <div class="md:hidden">
                <label for="listado-ordenar" class="block text-sm font-medium text-gray-700">Ordenar por</label>
                <select id="listado-ordenar" wire:change="ordenarCon($event.target.value)"
                        class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 px-3 text-base shadow-sm focus:border-blue-700 focus:ring-2 focus:ring-blue-700">
                    @foreach ($ordenes as $clave => $texto)
                        <option value="{{ $clave }}:asc" @selected($ordenActivo === $clave && $direccion !== 'desc')>{{ $texto }} (ascendente)</option>
                        <option value="{{ $clave }}:desc" @selected($ordenActivo === $clave && $direccion === 'desc')>{{ $texto }} (descendente)</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label for="listado-por-pagina" class="block text-sm font-medium text-gray-700">Por página</label>
            <select id="listado-por-pagina" wire:model.live="porPagina"
                    class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 px-3 text-base shadow-sm focus:border-blue-700 focus:ring-2 focus:ring-blue-700 sm:text-sm">
                @foreach ($tamanos as $tamano)
                    <option value="{{ $tamano }}">{{ $tamano }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <span wire:loading.delay wire:target="buscar,porPagina,filtros,ordenarPor,ordenarCon,previousPage,nextPage,quitarFiltro,limpiarFiltros"
          class="inline-flex min-h-11 items-center gap-2 text-sm text-gray-600" role="status">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        Cargando…
    </span>
</div>
