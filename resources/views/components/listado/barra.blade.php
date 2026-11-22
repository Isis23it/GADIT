@props(['etiqueta' => 'Buscar', 'tamanos' => [10, 25, 50, 100]])

<div {{ $attributes->class(['flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end']) }}>
    <div class="md:w-80">
        <label for="listado-buscar" class="block text-sm font-medium text-gray-700">{{ $etiqueta }}</label>
        <input id="listado-buscar" type="search" wire:model.live.debounce.300ms="buscar" autocomplete="off"
               class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 px-3 text-base shadow-sm focus:border-blue-700 focus:ring-2 focus:ring-blue-700 sm:text-sm">
    </div>

    {{ $slot }}

    <div>
        <label for="listado-por-pagina" class="block text-sm font-medium text-gray-700">Por página</label>
        <select id="listado-por-pagina" wire:model.live="porPagina"
                class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 px-3 text-base shadow-sm focus:border-blue-700 focus:ring-2 focus:ring-blue-700 sm:text-sm">
            @foreach ($tamanos as $tamano)
                <option value="{{ $tamano }}">{{ $tamano }}</option>
            @endforeach
        </select>
    </div>

    <span wire:loading.delay wire:target="buscar,porPagina,filtros,ordenarPor,previousPage,nextPage,quitarFiltro,limpiarFiltros"
          class="inline-flex min-h-11 items-center gap-2 text-sm text-gray-600" role="status">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        Cargando…
    </span>
</div>
