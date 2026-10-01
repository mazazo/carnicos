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
    @php($authUser = Auth::user())
    <header class="border-b bg-white" x-data="{ open: false }">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('landing') }}" class="font-semibold text-slate-900">Carnicos SaaS</a>
                @if($authUser?->isAdmin())
                    <a href="{{ route('dashboard.enterprise') }}" class="hidden rounded-lg border border-slate-300 bg-slate-50 px-3 py-1 text-sm font-semibold text-slate-700 hover:bg-slate-100 sm:inline-flex">
                        Dashboard Enterprise
                    </a>
                    <a href="{{ route('dashboard.pro') }}" class="hidden rounded-lg border border-slate-300 bg-slate-50 px-3 py-1 text-sm font-semibold text-slate-700 hover:bg-slate-100 sm:inline-flex">
                        Dashboard Pro
                    </a>
                @endif
                <a href="{{ route('billing.plans') }}" class="hidden rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 sm:inline-flex">
                    Planes
                </a>
            </div>

            {{-- Desktop nav --}}
            <nav class="hidden items-center gap-3 text-sm sm:flex">
                <a href="{{ route('animals.index') }}" class="rounded px-3 py-1 hover:bg-slate-100">Animales</a>
                <a href="{{ route('cuts.index') }}" class="rounded px-3 py-1 hover:bg-slate-100">Cortes</a>
                @if($authUser?->carniceria_id && $authUser->esDueno())
                    <a href="{{ route('cuenta.usuarios') }}" class="rounded px-3 py-1 hover:bg-slate-100">Usuarios</a>
                    <a href="{{ route('cuenta.tipos-animal') }}" class="rounded px-3 py-1 hover:bg-slate-100">Tipos</a>
                @endif
                <a href="{{ route('billing.plans') }}" class="rounded px-3 py-1 hover:bg-slate-100">Planes</a>
                <span class="font-medium text-slate-700">{{ $authUser?->full_name ?? 'Invitado' }}</span>
                @if($authUser?->isAdmin())
                    <a href="{{ route('admin.config.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 shadow-sm transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700" title="Configuracion" aria-label="Abrir configuracion">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.757.426 1.757 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.757-2.924 1.757-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.757-.426-1.757-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </a>
                @endif
                @if($authUser)
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded border border-slate-300 px-3 py-1 hover:bg-slate-100">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded border border-slate-300 px-3 py-1 hover:bg-slate-100">Ingresar</a>
                @endif
            </nav>

            {{-- Mobile hamburger --}}
            <button @click="open = !open" class="flex items-center justify-center rounded-lg border border-slate-200 p-2 text-slate-700 sm:hidden" aria-label="Menú">
                <svg x-show="!open" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="open" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Mobile dropdown --}}
        <div x-show="open" x-transition class="border-t border-slate-100 bg-white px-4 pb-3 sm:hidden">
            <nav class="flex flex-col gap-1 text-sm pt-2">
                @if($authUser?->isAdmin())
                    <a href="{{ route('dashboard.enterprise') }}" class="rounded px-3 py-2 hover:bg-slate-100">Dashboard Enterprise</a>
                    <a href="{{ route('dashboard.pro') }}" class="rounded px-3 py-2 hover:bg-slate-100">Dashboard Pro</a>
                    <a href="{{ route('admin.config.index') }}" class="flex items-center gap-2 rounded px-3 py-2 hover:bg-slate-100">
                        <svg class="h-4 w-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.757.426 1.757 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.757-2.924 1.757-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.757-.426-1.757-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Configuracion
                    </a>
                @endif
                <a href="{{ route('animals.index') }}" class="rounded px-3 py-2 hover:bg-slate-100">Animales</a>
                <a href="{{ route('cuts.index') }}" class="rounded px-3 py-2 hover:bg-slate-100">Cortes</a>
                @if($authUser?->carniceria_id && $authUser->esDueno())
                    <a href="{{ route('cuenta.usuarios') }}" class="rounded px-3 py-2 hover:bg-slate-100">Usuarios</a>
                    <a href="{{ route('cuenta.tipos-animal') }}" class="rounded px-3 py-2 hover:bg-slate-100">Tipos de animal</a>
                @endif
                <a href="{{ route('billing.plans') }}" class="rounded px-3 py-2 font-semibold text-emerald-700 hover:bg-emerald-50">Planes</a>
                @if($authUser)
                    <form method="POST" action="{{ route('logout') }}" class="mt-1">
                        @csrf
                        <button type="submit" class="w-full rounded border border-slate-300 px-3 py-2 text-left text-sm hover:bg-slate-100">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded px-3 py-2 hover:bg-slate-100">Ingresar</a>
                @endif
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>