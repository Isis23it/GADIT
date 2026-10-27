@props(['color' => 'gris'])
@php
    $clases = match ($color) {
        'verde' => 'bg-green-100 text-green-800',
        'ambar' => 'bg-amber-100 text-amber-800',
        'rojo' => 'bg-red-100 text-red-800',
        'azul' => 'bg-blue-100 text-blue-800',
        'gris' => 'bg-gray-100 text-gray-700',
        default => throw new \InvalidArgumentException("Color de insignia desconocido: {$color}"),
    };
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', $clases]) }}>{{ $slot }}</span>
