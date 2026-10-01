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
    @php
        $authUser = Auth::user();
        $carniceria = $authUser?->carniceria;
        $suscripcion = $carniceria?->suscripcionVigente();
        $plan = $suscripcion ? $carniceria->planVigente() : null;
        $esDueno = $authUser?->carniceria_id && $authUser->esDueno();
        $iniciales = collect(explode(' ', trim($authUser?->full_name ?? '')))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    @endphp

    <div x-data="{ menu: false }" @keydown.escape.window="menu = false">
        {{-- Barra superior (celular) --}}
        <header class="sticky top-0 z-30 flex items-center justify-between bg-stone-950 px-4 py-3 text-white lg:hidden print:hidden">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500 text-base">🔪</span>
                <span class="font-black tracking-tight">Carnico</span>
            </a>
            <button type="button" @click="menu = true" class="rounded-lg p-2 text-stone-300 hover:bg-white/10" aria-label="Abrir menú">
                <x-ui.icon name="menu" class="h-6 w-6" />
            </button>
        </header>

        {{-- Fondo oscuro detrás del menú en el celular --}}
        <div x-show="menu" x-cloak x-transition.opacity @click="menu = false" class="fixed inset-0 z-40 bg-stone-950/60 lg:hidden"></div>

        {{-- Barra lateral --}}
        <aside
            :class="menu && 'translate-x-0!'"
            class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-stone-950 text-stone-300 transition-transform duration-200 lg:translate-x-0 print:hidden"
        >
            <div class="flex items-center justify-between px-5 pb-4 pt-5">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-xl shadow-lg shadow-amber-500/20">🔪</span>
                    <span class="min-w-0">
                        <span class="block text-lg font-black leading-tight tracking-tight text-white">Carnico</span>
                        @if ($carniceria)
                            <span class="block truncate text-xs text-stone-400">{{ $carniceria->nombre }}</span>
                        @endif
                    </span>
                </a>
                <button type="button" @click="menu = false" class="rounded-lg p-1.5 text-stone-400 hover:bg-white/10 lg:hidden" aria-label="Cerrar menú">
                    <x-ui.icon name="x" />
                </button>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-2">
                <div class="space-y-1">
                    <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-stone-500">Operación</p>
                    @if ($authUser?->carniceria_id)
                        <x-ui.nav-link :href="route('dashboard')" icon="home" :active="request()->routeIs('dashboard*')">Inicio</x-ui.nav-link>
                    @endif
                    @if ($authUser?->puede('ingresos'))
                        <x-ui.nav-link :href="route('ingresos.index')" icon="inbox" :active="request()->routeIs('ingresos.*')">Ingresos</x-ui.nav-link>
                    @endif
                    @if ($authUser?->puede('producciones'))
                        <x-ui.nav-link :href="route('producciones.index')" icon="chart" :active="request()->routeIs('producciones.*', 'produccion')">Producciones</x-ui.nav-link>
                    @endif
                    @if ($authUser?->puede('cortes'))
                        <x-ui.nav-link :href="route('cuts.index')" icon="tag" :active="request()->routeIs('cuts.*')">Cortes</x-ui.nav-link>
                    @endif
                    {{-- Solo con Plan Completo (incluye la app) y permiso de app --}}
                    @if ($authUser?->carniceria_id && $authUser->puedeDescargarApp())
                        <x-ui.nav-link :href="route('app.android')" icon="download" :active="request()->routeIs('app.android*')">App Android</x-ui.nav-link>
                    @endif
                </div>

                {{-- Usuarios y planes: solo el dueño (y el administrador) --}}
                @if ($esDueno || $authUser?->isAdmin())
                    <div class="space-y-1">
                        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-stone-500">Cuenta</p>
                        @if ($esDueno)
                            <x-ui.nav-link :href="route('cuenta.usuarios')" icon="users" :active="request()->routeIs('cuenta.usuarios')">Usuarios</x-ui.nav-link>
                        @endif
                        <x-ui.nav-link :href="route('billing.plans')" icon="card" :active="request()->routeIs('billing.*')">Planes</x-ui.nav-link>
                    </div>
                @endif

                @if ($authUser?->isAdmin())
                    <div class="space-y-1">
                        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-stone-500">Administración</p>
                        <x-ui.nav-link :href="route('admin.config.index')" icon="cog" :active="request()->routeIs('admin.*')">Configuración</x-ui.nav-link>
                        <x-ui.nav-link :href="route('dashboard.enterprise')" icon="chart" :active="request()->routeIs('dashboard.enterprise')">Vista Plan Completo</x-ui.nav-link>
                        <x-ui.nav-link :href="route('dashboard.pro')" icon="chart" :active="request()->routeIs('dashboard.pro')">Vista Planes 1 y 2</x-ui.nav-link>
                    </div>
                @endif
            </nav>

            {{-- Plan vigente --}}
            @if ($plan && $esDueno)
                <a href="{{ route('billing.plans') }}" class="mx-3 mb-3 block rounded-xl border border-white/10 bg-white/5 px-4 py-3 hover:bg-white/10">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-stone-500">Tu plan</p>
                    <p class="text-sm font-semibold text-white">{{ $plan->nombre }}</p>
                    <p @class(['text-xs', 'text-amber-400' => $suscripcion->isTrial(), 'text-stone-400' => ! $suscripcion->isTrial()])>
                        {{ $suscripcion->isTrial() ? 'Prueba gratis' : 'Vigente' }} · {{ $suscripcion->diasRestantes() }} {{ $suscripcion->diasRestantes() === 1 ? 'día' : 'días' }}
                    </p>
                </a>
            @endif

            {{-- Usuario --}}
            <div class="flex items-center gap-3 border-t border-white/10 px-4 py-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-stone-800 text-xs font-bold text-amber-400">{{ $iniciales ?: '?' }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-white">{{ $authUser?->full_name ?? 'Invitado' }}</p>
                    <p class="truncate text-xs text-stone-500">
                        {{ $authUser?->isAdmin() ? 'Administrador' : ($authUser?->esDueno() ? 'Dueño' : 'Empleado') }}
                    </p>
                </div>
                @if ($authUser)
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg p-2 text-stone-400 hover:bg-white/10 hover:text-white" title="Salir" aria-label="Salir">
                            <x-ui.icon name="logout" />
                        </button>
                    </form>
                @endif
            </div>
        </aside>

        <main class="lg:pl-64 print:pl-0">
            <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
