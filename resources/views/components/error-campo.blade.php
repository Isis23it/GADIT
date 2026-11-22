@props(['para'])

@error($para)
    <p id="{{ $para }}-error" {{ $attributes->class(['mt-1 flex items-start gap-1.5 text-sm text-red-600']) }}>
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
        </svg>
        <span>{{ $message }}</span>
    </p>
@enderror
