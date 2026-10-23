<div>
    <form wire:submit="iniciar" novalidate>
        {{-- Correo --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
                Correo electrónico
            </label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autocomplete="email"
                autofocus
                required
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm
                       @error('email') border-red-500 @enderror"
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Contraseña --}}
        <div class="mt-4" x-data="{ visible: false }">
            <label for="password" class="block text-sm font-medium text-gray-700">
                Contraseña
            </label>
            <div class="relative mt-1">
                <input
                    wire:model="password"
                    id="password"
                    :type="visible ? 'text' : 'password'"
                    autocomplete="current-password"
                    required
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm pr-10
                           @error('password') border-red-500 @enderror"
                >
                <button
                    type="button"
                    @click="visible = !visible"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                    :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                >
                    <svg x-show="!visible" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="visible" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Botón --}}
        <div class="mt-6">
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm
                       hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2
                       focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove>Entrar</span>
                <span wire:loading>Verificando…</span>
            </button>
        </div>

        {{-- Enlace de recuperación --}}
        <div class="mt-4 text-center">
            <a
                href="{{ route('password.request') }}"
                class="text-sm text-indigo-600 hover:text-indigo-500"
            >
                Olvidé mi contraseña
            </a>
        </div>
    </form>
</div>
