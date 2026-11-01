<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'GADIT') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 shrink-0 border-r border-gray-200 bg-white lg:block">
            <div class="flex min-h-16 items-center border-b border-gray-200 px-6">
                <span class="text-xl font-bold tracking-tight text-gray-900">GADIT</span>
            </div>
            <x-menu-lateral :rol="auth()->user()->rol" />
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex min-h-16 items-center justify-between gap-4 border-b border-gray-200 bg-white px-6">
                <h1 class="text-xl font-semibold text-gray-900">{{ $title ?? '' }}</h1>
                <div class="flex items-center gap-3">
                    {{ $acciones ?? '' }}
                    <x-menu-usuario :usuario="auth()->user()" />
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1280px] flex-1 px-6 py-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
