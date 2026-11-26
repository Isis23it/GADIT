@props(['errorRegla' => null])

{{-- RF-03: validación en servidor; el aviso rojo de regla de negocio va sobre los campos. --}}
<form novalidate {{ $attributes }}>
    <x-formulario.aviso-regla :mensaje="$errorRegla" />

    <div class="space-y-4">
        {{ $slot }}
    </div>
</form>
