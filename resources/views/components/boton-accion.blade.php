@props(['href' => null])

@php
    $clases = 'fixed bottom-4 right-4 z-30 flex h-14 w-14 items-center justify-center rounded-full bg-indigo-700 text-white shadow-lg hover:bg-indigo-800 md:static md:z-auto md:h-auto md:min-h-11 md:w-auto md:rounded-lg md:px-4 md:shadow-none';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>
@else
    <button type="button" {{ $attributes->merge(['class' => $clases]) }}>
@endif
    <span aria-hidden="true" class="text-3xl leading-none md:hidden">+</span>
    <span class="sr-only text-base font-medium md:not-sr-only">{{ $slot }}</span>
@if ($href)
    </a>
@else
    </button>
@endif
