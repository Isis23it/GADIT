@props(['columna', 'orden' => '', 'direccion' => 'asc'])

@php($activa = $orden === $columna)
<th scope="col" aria-sort="{{ $activa ? ($direccion === 'desc' ? 'descending' : 'ascending') : 'none' }}"
    {{ $attributes->class(['px-4 py-0 font-medium']) }}>
    <button type="button" wire:click="ordenarPor('{{ $columna }}')"
            class="-mx-2 inline-flex min-h-11 items-center gap-1 rounded px-2 text-left font-medium hover:text-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">
        {{ $slot }}
        <span aria-hidden="true" class="text-xs text-gray-500">{{ $activa ? ($direccion === 'desc' ? '▼' : '▲') : '↕' }}</span>
    </button>
</th>
