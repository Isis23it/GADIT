@props(['paginador'])

@if ($paginador->hasPages())
    <nav aria-label="Paginación" class="mt-4 flex items-center justify-between gap-3">
        <button type="button" wire:click="previousPage" @disabled($paginador->onFirstPage())
                class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
            Anterior
        </button>
        <p class="text-sm text-gray-700">Página {{ $paginador->currentPage() }} de {{ $paginador->lastPage() }}</p>
        <button type="button" wire:click="nextPage" @disabled(! $paginador->hasMorePages())
                class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
            Siguiente
        </button>
    </nav>
@endif
