<div>
    <p class="text-sm text-gray-700">{{ $usuarios->count() === 1 ? '1 registro' : $usuarios->count().' registros' }}</p>

    {{-- Escritorio y tableta: tabla (RF-81) --}}
    <div class="mt-3 hidden overflow-x-auto rounded-lg border border-gray-200 bg-white md:block lg:overflow-visible">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <caption class="sr-only">Lista de usuarios</caption>
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th scope="col" class="px-4 py-3 font-medium lg:sticky lg:top-0">Nombre</th>
                    <th scope="col" class="px-4 py-3 font-medium lg:sticky lg:top-0">Correo</th>
                    <th scope="col" class="px-4 py-3 font-medium lg:sticky lg:top-0">Rol</th>
                    <th scope="col" class="px-4 py-3 font-medium lg:sticky lg:top-0">Estado</th>
                    <th scope="col" class="px-4 py-3 font-medium lg:sticky lg:top-0">Fecha de alta</th>
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
</div>
