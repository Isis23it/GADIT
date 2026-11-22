<div>
    @if ($enviado)
        <p class="text-sm text-gray-700">
            Si el correo existe, enviamos las instrucciones para restablecer la contraseña.
        </p>
        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center px-2 text-sm text-indigo-600 hover:text-indigo-500">
                Volver al inicio de sesión
            </a>
        </div>
    @else
        <p class="mb-5 text-sm text-gray-600">
            Ingresa tu correo y te enviaremos el enlace para restablecer tu contraseña.
        </p>

        <form wire:submit="enviar" novalidate>
            <div>
                <x-etiqueta para="email">Correo electrónico</x-etiqueta>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    autocomplete="email"
                    autofocus
                    required
                    aria-required="true"
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                    class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 px-3 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 sm:text-sm
                           @error('email') border-red-500 @enderror"
                >
                <x-error-campo para="email" />
            </div>

            <div class="mt-6">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="min-h-11 w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm
                           hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2
                           focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove>Enviar instrucciones</span>
                    <span wire:loading>Enviando…</span>
                </button>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center px-2 text-sm text-indigo-600 hover:text-indigo-500">
                    Volver al inicio de sesión
                </a>
            </div>
        </form>
    @endif
</div>
