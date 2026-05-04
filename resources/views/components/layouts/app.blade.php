<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Carnicos SaaS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <a href="{{ route('dashboard.enterprise') }}" class="font-semibold text-slate-900">Carnicos SaaS</a>
            <nav class="flex items-center gap-3 text-sm">
                <a href="{{ route('dashboard.enterprise') }}" class="rounded px-3 py-1 hover:bg-slate-100">Dashboard Enterprise</a>
                <a href="{{ route('dashboard.pro') }}" class="rounded px-3 py-1 hover:bg-slate-100">Dashboard Pro</a>
                <a href="{{ route('animals.index') }}" class="rounded px-3 py-1 hover:bg-slate-100">Animales</a>
                <a href="{{ route('cuts.index') }}" class="rounded px-3 py-1 hover:bg-slate-100">Cortes</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded border border-slate-300 px-3 py-1 hover:bg-slate-100">Salir</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
