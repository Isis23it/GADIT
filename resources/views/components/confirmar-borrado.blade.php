@props(['nombre', 'registro', 'motivo' => false])
<x-modal :nombre="$nombre" :titulo="'¿Eliminar '.$registro.'?'" ancho="md">
    <div class="space-y-4 text-sm text-gray-900">
        {{ $slot }}

        @if ($motivo)
            <div>
                <label for="motivo-{{ $nombre }}" class="block font-medium">Motivo (opcional)</label>
                <textarea id="motivo-{{ $nombre }}" name="motivo" rows="3" wire:model="motivo" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"></textarea>
            </div>
        @endif
    </div>

    <x-slot:pie>
        <button type="button" x-on:click="cerrar(true)" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50">Cancelar</button>
        <button type="button" x-on:click="cerrar(true)" {{ $attributes }} class="rounded-md bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800">Eliminar</button>
    </x-slot:pie>
</x-modal>
