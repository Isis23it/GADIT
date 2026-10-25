<div
    x-data="{
        avisos: [],
        siguiente: 1,
        agregar(detalle) {
            const aviso = {
                id: this.siguiente++,
                tipo: detalle.tipo ?? 'exito',
                mensaje: detalle.mensaje ?? '',
            };
            this.avisos.push(aviso);
            if (aviso.tipo === 'exito') {
                setTimeout(() => this.quitar(aviso.id), 4000);
            }
        },
        quitar(id) {
            this.avisos = this.avisos.filter((aviso) => aviso.id !== id);
        },
    }"
    x-on:aviso.window="agregar($event.detail)"
    aria-live="polite"
    class="pointer-events-none fixed inset-x-0 top-0 z-50 flex flex-col gap-2 p-4 sm:inset-x-auto sm:right-0 sm:w-96"
>
    <template x-for="aviso in avisos" :key="aviso.id">
        <div
            :role="aviso.tipo === 'exito' ? 'status' : 'alert'"
            :class="{
                'bg-green-100 text-green-800 border-green-800/20': aviso.tipo === 'exito',
                'bg-amber-100 text-amber-800 border-amber-800/20': aviso.tipo === 'advertencia',
                'bg-red-100 text-red-800 border-red-800/20': aviso.tipo === 'error',
            }"
            class="pointer-events-auto flex items-start justify-between gap-3 rounded-md border px-4 py-3 text-sm shadow-sm"
        >
            <span x-text="aviso.mensaje"></span>
            <button type="button" x-on:click="quitar(aviso.id)" aria-label="Cerrar aviso" class="flex min-h-11 min-w-11 items-center justify-center font-medium">✕</button>
        </div>
    </template>
</div>
