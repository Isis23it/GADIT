<div>
    <x-listado.barra etiqueta="Buscar usuarios" :tamanos="$this->tamanosPagina()"
                     :ordenes="['nombre' => 'Nombre', 'correo' => 'Correo', 'rol' => 'Rol', 'estado' => 'Estado', 'alta' => 'Fecha de alta']"
                     :orden-activo="$ordenActivo" :direccion="$direccion">
        <div>
            <label for="filtro-rol" class="block text-sm font-medium text-gray-700">Rol</label>
            <select id="filtro-rol" wire:model.live="filtros.rol"
                    class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 px-3 text-base shadow-sm focus:border-blue-700 focus:ring-2 focus:ring-blue-700 sm:text-sm">
                <option value="">Todos</option>
                @foreach ($roles as $rolOpcion)
                    <option value="{{ $rolOpcion->value }}">{{ $rolOpcion->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filtro-estado" class="block text-sm font-medium text-gray-700">Estado</label>
            <select id="filtro-estado" wire:model.live="filtros.estado"
                    class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 px-3 text-base shadow-sm focus:border-blue-700 focus:ring-2 focus:ring-blue-700 sm:text-sm">
                <option value="">Todos</option>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </div>
    </x-listado.barra>

    <x-listado.fichas :fichas="$fichas" />

    <x-listado.resumen :total="$usuarios->total()" class="mt-3" />

    @if ($usuarios->total() === 0)
        <x-listado.vacio plural="usuarios" :filtrado="$hayFiltros" />
    @else
        {{-- Escritorio y tableta: tabla (RF-81) --}}
        <div class="mt-3 hidden overflow-x-auto rounded-lg border border-gray-200 bg-white md:block lg:overflow-visible">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <caption class="sr-only">Lista de usuarios</caption>
                <thead class="bg-gray-50 text-left text-gray-600 lg:sticky lg:top-0">
                    <tr>
                        <x-listado.encabezado columna="nombre" :orden="$ordenActivo" :direccion="$direccion">Nombre</x-listado.encabezado>
                        <x-listado.encabezado columna="correo" :orden="$ordenActivo" :direccion="$direccion">Correo</x-listado.encabezado>
                        <x-listado.encabezado columna="rol" :orden="$ordenActivo" :direccion="$direccion">Rol</x-listado.encabezado>
                        <x-listado.encabezado columna="estado" :orden="$ordenActivo" :direccion="$direccion">Estado</x-listado.encabezado>
                        <x-listado.encabezado columna="alta" :orden="$ordenActivo" :direccion="$direccion">Fecha de alta</x-listado.encabezado>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($usuarios as $usuario)
                        <tr wire:key="fila-{{ $usuario->id }}">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $usuario->nombre }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $usuario->email }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $usuario->rol->label() }}</td>
                            <td class="px-4 py-3">
                                <x-insignia :color="$usuario->activo ? 'verde' : 'gris'">{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</x-insignia>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ \App\Support\Formato::fecha($usuario->created_at) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Móvil: tarjetas con las primeras 4 columnas, la primera como título (RF-81) --}}
        <ul class="mt-3 space-y-3 md:hidden" aria-label="Usuarios">
            @foreach ($usuarios as $usuario)
                <li wire:key="tarjeta-{{ $usuario->id }}" class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                    <p class="font-semibold text-gray-900">{{ $usuario->nombre }}</p>
                    <p class="break-all text-sm text-gray-700">{{ $usuario->email }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-700">
                        <span>{{ $usuario->rol->label() }}</span>
                        <x-insignia :color="$usuario->activo ? 'verde' : 'gris'">{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</x-insignia>
                    </div>
                </li>
            @endforeach
        </ul>

        <x-listado.paginacion :paginador="$usuarios" />
    @endif
</div>
