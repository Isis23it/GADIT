@props(['rol', 'actual' => null, 'contadores' => []])

@php
    $actual = trim($actual ?? request()->path(), '/');
@endphp

<nav aria-label="Menú principal" class="flex flex-col gap-6 px-4 py-6">
    @foreach (\App\Support\Menu::para($rol) as $grupo)
        <div>
            <h2 class="px-3 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $grupo['grupo'] }}</h2>
            <ul class="mt-2 space-y-1">
                @foreach ($grupo['opciones'] as $opcion)
                    @php
                        $activa = \App\Support\Menu::esActual($actual, $opcion['ruta']);
                        $contador = $contadores[$opcion['ruta']] ?? 0;
                    @endphp
                    <li>
                        <a href="{{ url($opcion['ruta']) }}"
                           @if ($activa) aria-current="page" @endif
                           @class([
                               'flex min-h-11 items-center justify-between rounded-lg px-3 text-base font-medium lg:text-sm',
                               'bg-indigo-50 text-indigo-700' => $activa,
                               'text-gray-700 hover:bg-gray-100' => ! $activa,
                           ])>
                            <span>{{ $opcion['etiqueta'] }}</span>
                            @if ($contador > 0)
                                <span class="rounded-full bg-red-700 px-2 py-0.5 text-xs font-semibold text-white">{{ $contador }}<span class="sr-only"> con alertas</span></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
