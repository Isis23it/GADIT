@props(['usuario', 'plano' => false])

@if ($plano)
    <div class="border-t border-gray-200 px-4 py-4">
        <p class="px-3 text-base font-medium text-gray-900">{{ $usuario->nombre }}</p>
        <p class="px-3 text-sm text-gray-500">{{ $usuario->rol->label() }}</p>
        <a href="{{ route('perfil') }}"
           class="mt-2 flex min-h-11 items-center rounded-lg px-3 text-base text-gray-700 hover:bg-gray-100">Mi perfil</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="flex min-h-11 w-full items-center rounded-lg px-3 text-left text-base text-gray-700 hover:bg-gray-100">Cerrar sesión</button>
        </form>
    </div>
@else
    <div x-data="{ abierto: false }"
         x-on:keydown.escape="abierto = false"
         x-on:click.outside="abierto = false"
         class="relative">
        <button type="button"
                x-on:click="abierto = ! abierto"
                aria-haspopup="menu"
                x-bind:aria-expanded="abierto"
                class="flex min-h-11 items-center rounded-lg px-3 text-left text-base font-medium text-gray-700 hover:bg-gray-100 lg:text-sm">
            <span>
                <span class="block">{{ $usuario->nombre }}</span>
                <span class="block text-xs font-normal text-gray-500">{{ $usuario->rol->label() }}</span>
            </span>
        </button>

        <div x-cloak x-show="abierto" role="menu"
             class="absolute right-0 z-20 mt-2 w-56 rounded-xl bg-white py-1 shadow-lg ring-1 ring-gray-900/5">
            <a href="{{ route('perfil') }}" role="menuitem"
               class="flex min-h-11 items-center px-4 text-base text-gray-700 hover:bg-gray-100 lg:text-sm">Mi perfil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" role="menuitem"
                        class="flex min-h-11 w-full items-center px-4 text-left text-base text-gray-700 hover:bg-gray-100 lg:text-sm">Cerrar sesión</button>
            </form>
        </div>
    </div>
@endif
