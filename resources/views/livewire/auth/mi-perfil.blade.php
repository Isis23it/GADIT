<div class="mx-auto w-full max-w-xl space-y-6">
    <section class="rounded-xl bg-white px-4 py-6 shadow-sm ring-1 ring-gray-900/5 md:px-6">
        <h2 class="text-base font-semibold text-gray-900">Datos de la cuenta</h2>

        <form wire:submit="guardarNombre" novalidate class="mt-4 space-y-4">
            <div>
                <x-etiqueta para="nombre" obligatorio>Nombre</x-etiqueta>
                <input wire:model="nombre" id="nombre" type="text" autocomplete="name" required aria-required="true"
                       @error('nombre') aria-invalid="true" aria-describedby="nombre-error" @enderror
                       class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 sm:text-sm @error('nombre') border-red-500 @enderror">
                <x-error-campo para="nombre" />
            </div>

            <div>
                <p class="block text-sm font-medium text-gray-700">Correo electrónico</p>
                <p class="mt-1 text-sm text-gray-900">{{ $usuario->email }}</p>
                <p class="mt-1 text-sm text-gray-500">El correo y el rol los cambia un administrador.</p>
            </div>

            <button type="submit" wire:loading.attr="disabled"
                    class="min-h-11 w-full rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                Guardar nombre
            </button>
        </form>
    </section>

    <section class="rounded-xl bg-white px-4 py-6 shadow-sm ring-1 ring-gray-900/5 md:px-6">
        <h2 class="text-base font-semibold text-gray-900">Cambiar contraseña</h2>

        <form wire:submit="cambiarContrasena" novalidate class="mt-4 space-y-4">
            <div>
                <x-etiqueta para="password_actual" obligatorio>Contraseña actual</x-etiqueta>
                <input wire:model="password_actual" id="password_actual" type="password" autocomplete="current-password" required aria-required="true"
                       @error('password_actual') aria-invalid="true" aria-describedby="password_actual-error" @enderror
                       class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 sm:text-sm @error('password_actual') border-red-500 @enderror">
                <x-error-campo para="password_actual" />
            </div>

            <div>
                <x-etiqueta para="password" obligatorio>Nueva contraseña</x-etiqueta>
                <input wire:model="password" id="password" type="password" autocomplete="new-password" required aria-required="true"
                       aria-describedby="password-ayuda @error('password') password-error @enderror"
                       @error('password') aria-invalid="true" @enderror
                       class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 sm:text-sm @error('password') border-red-500 @enderror">
                <p id="password-ayuda" class="mt-1 text-sm text-gray-500">Mínimo 12 caracteres.</p>
                <x-error-campo para="password" />
            </div>

            <div>
                @php($noCoincide = $password !== $password_confirmation && $errors->has('password'))
                <x-etiqueta para="password_confirmation" obligatorio>Confirmar nueva contraseña</x-etiqueta>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" required aria-required="true"
                       @if ($noCoincide) aria-invalid="true" aria-describedby="password-error" @endif
                       class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 sm:text-sm @if ($noCoincide) border-red-500 @endif">
            </div>

            <button type="submit" wire:loading.attr="disabled"
                    class="min-h-11 w-full rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                Cambiar contraseña
            </button>
        </form>
    </section>
</div>
