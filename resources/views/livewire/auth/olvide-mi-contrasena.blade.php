<div>
    @if ($enviado)
        <p class="text-sm text-gray-700">
            Si el correo existe, enviamos las instrucciones para restablecer la contraseña.
        </p>
        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                Volver al inicio de sesión
            </a>
        </div>
    @else
        <p class="mb-5 text-sm text-gray-600">
            Ingresa tu correo y te enviaremos el enlace para restablecer tu contraseña.
        </p>

        <form wire:submit="enviar" novalidate>
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

            <div class="mt-6">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm
                           hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2
                           focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove>Enviar instrucciones</span>
                    <span wire:loading>Enviando…</span>
                </button>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('login') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                    Volver al inicio de sesión
                </a>
            </div>
        </form>
    @endif
</div>
