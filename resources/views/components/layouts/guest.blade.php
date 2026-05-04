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
    <div class="flex min-h-screen items-center justify-center p-4 md:p-10">
        <!-- Card única: logo izquierda + formulario derecha -->
        <div class="flex w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-xl md:max-w-none md:w-auto">

            <!-- Panel izquierdo: logo (oculto en mobile) -->
            <div class="hidden md:flex w-64 shrink-0 flex-col items-center justify-center border-r border-slate-100 bg-slate-50 p-10">
                <a href="{{ url('/') }}">
                    <div class="flex flex-col items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" class="h-14 w-14" fill="none">
                            <circle cx="32" cy="32" r="32" fill="#1e3a2f"/>
                            <path d="M18 44c2-8 6-14 14-18s14-2 14 4-6 12-14 14-14-4-14 0z" fill="#4ade80" opacity=".9"/>
                            <path d="M46 20c-2 8-6 14-14 18s-14 2-14-4 6-12 14-14 14 4 14 0z" fill="#86efac" opacity=".6"/>
                        </svg>
                        <span class="text-lg font-bold tracking-tight text-slate-800">Carnicos</span>
                        <span class="text-xs text-slate-500 text-center leading-tight">Gestión y análisis<br>de rendimientos</span>
                    </div>
                </a>
            </div>

            <!-- Panel derecho: formulario -->
            <div class="w-full p-8 md:w-80 md:shrink-0 md:p-10">
                <!-- Logo solo en mobile -->
                <div class="mb-6 flex flex-col items-center gap-1 md:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" class="h-10 w-10" fill="none">
                        <circle cx="32" cy="32" r="32" fill="#1e3a2f"/>
                        <path d="M18 44c2-8 6-14 14-18s14-2 14 4-6 12-14 14-14-4-14 0z" fill="#4ade80" opacity=".9"/>
                        <path d="M46 20c-2 8-6 14-14 18s-14 2-14-4 6-12 14-14 14 4 14 0z" fill="#86efac" opacity=".6"/>
                    </svg>
                    <span class="text-sm font-bold text-slate-800">Carnicos</span>
                </div>
                {{ $slot }}
            </div>

        </div>
    </div>
    @livewireScripts
</body>
</html>
