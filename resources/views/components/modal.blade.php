@props(['nombre', 'titulo', 'ancho' => 'md', 'descartar' => false])
@php
    $anchoClase = match ($ancho) {
        'md' => 'sm:max-w-[480px]',
        'lg' => 'sm:max-w-[640px]',
        default => throw new \InvalidArgumentException("Ancho de modal desconocido: {$ancho}"),
    };
@endphp
<div
    x-data="{
        abierto: false,
        sucio: false,
        preguntando: false,
        descartar: {{ $descartar ? 'true' : 'false' }},
        abrir() {
            this.sucio = false;
            this.preguntando = false;
            this.abierto = true;
        },
        cerrar(forzar = false) {
            if (! forzar && this.descartar && this.sucio) {
                this.preguntando = true;
                return;
            }
            this.abierto = false;
            this.preguntando = false;
        },
        descartarCambios() {
            this.$root.querySelectorAll('input, textarea, select').forEach((campo) => {
                if (campo.type === 'checkbox' || campo.type === 'radio') {
                    campo.checked = campo.defaultChecked;
                } else if (campo.tagName === 'SELECT') {
                    Array.from(campo.options).forEach((opcion) => opcion.selected = opcion.defaultSelected);
                } else {
                    campo.value = campo.defaultValue;
                }
                campo.dispatchEvent(new Event('input', { bubbles: true }));
            });
            this.sucio = false;
            this.cerrar(true);
        },
    }"
    x-on:abrir-modal.window="if ($event.detail.nombre === '{{ $nombre }}') abrir()"
    x-on:cerrar-modal.window="if ($event.detail.nombre === '{{ $nombre }}') cerrar(true)"
    x-on:keydown.escape.window="if (abierto) cerrar()"
    x-show="abierto"
    x-cloak
    class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4"
>
    <div class="fixed inset-0 bg-gray-900/50" x-on:click="cerrar()" aria-hidden="true"></div>

    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-{{ $nombre }}-titulo"
        x-trap.noscroll="abierto"
        x-on:input="sucio = true"
        x-on:change="sucio = true"
        class="relative flex h-full w-full flex-col bg-white shadow-xl sm:h-auto sm:max-h-[90vh] sm:rounded-lg {{ $anchoClase }}"
    >
        <div class="flex items-center justify-between border-b border-gray-300 px-6 py-4">
            <h2 id="modal-{{ $nombre }}-titulo" class="text-lg font-semibold text-gray-900">{{ $titulo }}</h2>
            <button type="button" x-on:click="cerrar()" aria-label="Cerrar" class="rounded-md p-1 text-gray-600 hover:text-gray-900">✕</button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-4">{{ $slot }}</div>

        @isset($pie)
            <div class="flex justify-end gap-2 border-t border-gray-300 px-6 py-4">{{ $pie }}</div>
        @endisset

        <div x-show="preguntando" x-cloak class="absolute inset-0 flex items-center justify-center bg-white/95 p-6 sm:rounded-lg">
            <div class="text-center">
                <p class="text-lg font-semibold text-gray-900">¿Descartar los cambios?</p>
                <div class="mt-4 flex justify-center gap-2">
                    <button type="button" x-on:click="descartarCambios()" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50">Descartar</button>
                    <button type="button" x-on:click="preguntando = false" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Seguir editando</button>
                </div>
            </div>
        </div>
    </div>
</div>
