<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/inertia.js'])
    <x-inertia::head />
</head>
<body class="bg-midnight font-sans text-zinc-100 antialiased textured">
<x-inertia::app />
</body>
</html>
