<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carnico</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen">
    {{-- Layout de Livewire de ingreso, registro y verificación (sin la barra lateral de la app):
         tarjeta compacta y centrada, que entra completa en tablet. Cada pantalla va en $slot. --}}
    <div class="flex min-h-screen items-center justify-center px-4 py-6 sm:px-6">
        <div class="w-full max-w-4xl overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-xl shadow-stone-300/40">
            <div class="grid grid-cols-1 md:grid-cols-[0.9fr_1.1fr]">
                <aside class="relative overflow-hidden bg-stone-950 p-6 text-white md:p-8">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.22),transparent_45%)]"></div>
                    <div class="relative flex h-full flex-col">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500 text-2xl shadow-lg shadow-amber-500/20">🔪</span>
                            <span class="text-2xl font-black tracking-tight">Carnico</span>
                        </div>

                        <h2 class="mt-6 text-xl font-bold leading-tight md:mt-8 md:text-2xl">Tu carnicería, ordenada</h2>
                        <p class="mt-2 text-sm leading-6 text-stone-400">Ingresos, despostes y cortes con el rinde y la ganancia de cada producción.</p>

                        {{-- En el celular se oculta para que el formulario quede arriba --}}
                        <ul class="mt-6 hidden space-y-3 text-sm md:block">
                            @foreach ([
                                ['🐄', 'Ingresos de medias, cajones y cuartos'],
                                ['🔪', 'Despostes y cuarteos con su rinde'],
                                ['📈', 'Costo, venta y ganancia al instante'],
                                ['📱', 'También desde la app del celular'],
                            ] as [$emoji, $texto])
                                <li class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2.5">
                                    <span class="text-base">{{ $emoji }}</span>
                                    <span class="text-stone-200">{{ $texto }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <p class="mt-auto hidden pt-6 text-xs text-stone-500 md:block">Carnico · por nashi.dev</p>
                    </div>
                </aside>

                <section class="p-6 md:p-8">
                    {{ $slot }}
                </section>
            </div>
        </div>
    </div>
    @livewireScripts
</body>
</html>
