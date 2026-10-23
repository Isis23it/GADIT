<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GADIT') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 antialiased">

    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-[400px]">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">GADIT</h1>
                <p class="mt-1 text-sm text-gray-500">Control administrativo</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 px-8 py-10">
                {{ $slot }}
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
