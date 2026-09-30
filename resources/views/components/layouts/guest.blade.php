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
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
   
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-200/60">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[0.85fr_1.15fr]">
                <aside class="relative overflow-hidden bg-slate-900 p-5 text-white sm:p-7 md:p-8 lg:p-10">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(148,163,184,0.28),_transparent_35%)]"></div>
                    <div class="relative h-full">
                        <div class="mb-5 inline-flex items-center rounded-full border border-slate-700 bg-slate-800/80 px-3 py-1 text-xs font-medium uppercase tracking-[0.2em] text-slate-200">
                            Nashi
                        </div>

                        <h2 class="text-2xl font-bold leading-tight sm:text-3xl">Bienvenido</h2>
                        <p class="mt-3 max-w-md text-sm leading-6 text-slate-300">
                            Registrate para ingresar y comenzar a gestionar tus cortes, rendimientos y decisiones de negocio desde un mismo lugar.
                        </p>

                        <div class="mt-6 space-y-3 sm:mt-8 sm:space-y-4">
                            <div class="rounded-2xl border border-slate-700 bg-slate-800/60 p-4">
                                <p class="text-sm font-semibold text-white">Gestion centralizada</p>
                                <p class="mt-1 text-sm text-slate-300">Organizá cortes, animales y analisis sin perder trazabilidad.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-700 bg-slate-800/60 p-4">
                                <p class="text-sm font-semibold text-white">Rendimientos claros</p>
                                <p class="mt-1 text-sm text-slate-300">Seguí tus promedios y entregas con visibilidad real del negocio.</p>
                            </div>
                        </div>

                        <div class="mt-6 rounded-2xl border border-slate-700 bg-white/5 p-4 text-sm text-slate-200 sm:mt-8">
                            <p class="font-medium text-white">Tu cuenta te permite:</p>
                            <ul class="mt-3 space-y-2 text-slate-300">
                                <li>• Ingresar al dashboard</li>
                                <li>• Gestionar animales y cortes</li>
                                <li>• Guardar y comparar resultados</li>
                            </ul>
                        </div>

                    </div>
                </aside>

                <section class="p-5 sm:p-7 lg:p-10">
                    {{ $slot }}
                </section>
            </div>
        </div>
    </div>
    @livewireScripts
</body>
</html>
