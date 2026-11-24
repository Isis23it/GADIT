@props(['para', 'obligatorio' => false])
{{-- El asterisco solo sale si el campo lo declara con «obligatorio»: un campo opcional nunca debe mostrarlo. --}}

<label for="{{ $para }}" {{ $attributes->class(['block text-sm font-medium text-gray-700']) }}>
    {{ $slot }}@if ($obligatorio)<span class="text-red-600" aria-hidden="true"> *</span>@endif
</label>
