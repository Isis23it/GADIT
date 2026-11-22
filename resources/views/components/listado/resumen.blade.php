@props(['total', 'suma' => null])

<p {{ $attributes->class(['text-sm text-gray-700']) }} role="status">
    {{ $total === 1 ? '1 registro' : number_format($total).' registros' }}@if ($suma !== null) · Suma: {{ $suma }}@endif
</p>
