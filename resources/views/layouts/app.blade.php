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
    <div class="flex min-h-screen" x-data="{ menuAbierto: false }" x-on:keydown.escape.window="menuAbierto = false">
        <aside class="hidden w-64 shrink-0 border-r border-gray-200 bg-white lg:block">
            <div class="flex min-h-16 items-center border-b border-gray-200 px-6">
                <span class="text-xl font-bold tracking-tight text-gray-900">GADIT</span>
            </div>
            <x-menu-lateral :rol="auth()->user()->rol" />
        </aside>

        <div x-show="menuAbierto" x-cloak x-transition.opacity x-on:click="menuAbierto = false" class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" aria-hidden="true"></div>

        <aside id="menu-movil" x-show="menuAbierto" x-cloak x-trap.noscroll="menuAbierto" role="dialog" aria-modal="true" aria-label="Menú principal"
               x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
               class="fixed inset-y-0 left-0 z-50 flex w-4/5 max-w-xs flex-col overflow-y-auto bg-white shadow-xl lg:hidden">
            <div class="flex min-h-16 items-center justify-between border-b border-gray-200 pl-6 pr-2">
                <span class="text-xl font-bold tracking-tight text-gray-900">GADIT</span>
                <button type="button" x-on:click="menuAbierto = false" aria-label="Cerrar menú" class="flex min-h-11 min-w-11 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100">✕</button>
            </div>
            <div x-on:click="if ($event.target.closest('a')) menuAbierto = false" class="flex flex-1 flex-col">
                <x-menu-lateral :rol="auth()->user()->rol" />
                <div class="mt-auto md:hidden">
                    <x-menu-usuario :usuario="auth()->user()" :plano="true" />
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex min-h-16 items-center justify-between gap-2 border-b border-gray-200 bg-white px-4 md:px-6">
                <div class="flex min-w-0 items-center gap-2">
                    <button type="button" x-on:click="menuAbierto = true" x-bind:aria-expanded="menuAbierto" aria-controls="menu-movil" aria-label="Abrir menú"
                            class="-ml-2 flex min-h-11 min-w-11 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100 lg:hidden">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <h1 class="truncate text-lg font-semibold text-gray-900 md:text-xl">{{ $title ?? '' }}</h1>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    {{ $acciones ?? '' }}
                    <div class="hidden md:block">
                        <x-menu-usuario :usuario="auth()->user()" />
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1280px] flex-1 px-4 py-4 pb-24 md:px-6 md:py-6 md:pb-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-avisos />
    @livewireScripts
</body>
</html>
