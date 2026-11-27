@props(['mensaje' => null])

@if ($mensaje)
    <div role="alert" class="mb-4 rounded-md border border-red-800/20 bg-red-100 px-4 py-3 text-sm text-red-800">
        {{ $mensaje }}
    </div>
@endif
