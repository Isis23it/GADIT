<div class="mx-auto w-full max-w-xl space-y-6">
    <section class="rounded-xl bg-white px-4 py-6 shadow-sm ring-1 ring-gray-900/5 md:px-6">
        <h2 class="text-base font-semibold text-gray-900">Datos de la cuenta</h2>

        <form wire:submit="guardarNombre" novalidate class="mt-4 space-y-4">
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                <input wire:model="nombre" id="nombre" type="text" autocomplete="name" required
                       @error('nombre') aria-invalid="true" aria-describedby="nombre-error" @enderror
                       class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 sm:text-sm @error('nombre') border-red-500 @enderror">
                @error('nombre')
                    <p id="nombre-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
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
                <label for="password_actual" class="block text-sm font-medium text-gray-700">Contraseña actual</label>
                <input wire:model="password_actual" id="password_actual" type="password" autocomplete="current-password" required
                       @error('password_actual') aria-invalid="true" aria-describedby="password_actual-error" @enderror
                       class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 sm:text-sm @error('password_actual') border-red-500 @enderror">
                @error('password_actual')
                    <p id="password_actual-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Nueva contraseña</label>
                <input wire:model="password" id="password" type="password" autocomplete="new-password" required
                       aria-describedby="password-ayuda @error('password') password-error @enderror"
                       @error('password') aria-invalid="true" @enderror
                       class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 sm:text-sm @error('password') border-red-500 @enderror">
                <p id="password-ayuda" class="mt-1 text-sm text-gray-500">Mínimo 12 caracteres.</p>
                @error('password')
                    <p id="password-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar nueva contraseña</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" required
                       class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 sm:text-sm">
            </div>

            <button type="submit" wire:loading.attr="disabled"
                    class="min-h-11 w-full rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                Cambiar contraseña
            </button>
        </form>
    </section>
</div>
