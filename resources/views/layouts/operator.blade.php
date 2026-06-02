<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Sistem Antrian PESAT') : config('app.name', 'Sistem Antrian PESAT') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    {{ $slot }}
    @livewireScripts
</body>
</html>
