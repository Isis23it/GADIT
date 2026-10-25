@props(['codigo', 'mensaje'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mensaje }} · GADIT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center p-4">
    <main class="w-full max-w-md rounded-lg border border-gray-300 bg-white p-8 text-center shadow-sm">
        <p class="text-sm font-semibold text-gray-600">GADIT</p>
        <p class="mt-4 text-5xl font-semibold text-gray-900">{{ $codigo }}</p>
        <h1 class="mt-2 text-2xl font-semibold text-gray-900">{{ $mensaje }}</h1>
        <a href="{{ url('/') }}" class="mt-6 inline-block rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Ir al tablero</a>
    </main>
</body>
</html>
