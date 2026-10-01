@php
    $colores = [
        'emergency' => 'bg-red-700 text-white',
        'alert' => 'bg-red-600 text-white',
        'critical' => 'bg-red-600 text-white',
        'error' => 'bg-red-100 text-red-800 ring-1 ring-red-200',
        'warning' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-200',
        'notice' => 'bg-sky-100 text-sky-800 ring-1 ring-sky-200',
        'info' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'debug' => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.config.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Configuracion</a>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Logs de la aplicacion</h1>
            <p class="text-sm text-slate-500">storage/logs &middot; {{ $total }} entradas en el archivo, la mas reciente primero.</p>
        </div>

        @if ($archivo)
            <div class="flex gap-2">
                <button type="button" wire:click="descargar"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Descargar
                </button>
                <button type="button" wire:click="vaciar" wire:confirm="¿Vaciar {{ $archivo }}? No se puede deshacer."
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                    Vaciar
                </button>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if ($archivos->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
            Todavia no hay archivos de log.
        </div>
    @else
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3">
            <select wire:model.live="archivo" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                @foreach ($archivos as $a)
                    <option value="{{ $a['nombre'] }}">{{ $a['nombre'] }} ({{ \Illuminate\Support\Number::fileSize($a['bytes']) }})</option>
                @endforeach
            </select>

            <input type="search" wire:model.live.debounce.400ms="busqueda" placeholder="Buscar en mensajes y detalles del error..."
                   class="min-w-60 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">

            <div class="flex flex-wrap gap-1.5">
                <button type="button" wire:click="$set('nivel', '')" @class([
                    'rounded-md px-2.5 py-1 text-xs font-semibold',
                    'bg-slate-900 text-white' => $nivel === '',
                    'bg-slate-100 text-slate-600 hover:bg-slate-200' => $nivel !== '',
                ])>Todos</button>
                @foreach (\App\Support\Logs\LogReader::NIVELES as $n)
                    @if ($conteo->has($n))
                        <button type="button" wire:click="$set('nivel', '{{ $n }}')" @class([
                            'rounded-md px-2.5 py-1 text-xs font-semibold uppercase',
                            $colores[$n] => $nivel === $n || $nivel === '',
                            'bg-slate-50 text-slate-400 hover:text-slate-600' => $nivel !== '' && $nivel !== $n,
                        ])>{{ $n }} &middot; {{ $conteo[$n] }}</button>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white" wire:loading.class="opacity-60">
            @forelse ($entradas as $entrada)
                <div x-data="{ open: false }" wire:key="log-{{ $entradas->currentPage() }}-{{ $loop->index }}-{{ $entrada->fecha }}" class="border-b border-slate-100 last:border-0">
                    <button type="button" @click="open = !open" class="flex w-full items-start gap-3 px-4 py-3 text-left hover:bg-slate-50">
                        <span class="{{ $colores[$entrada->nivel] ?? $colores['debug'] }} mt-0.5 w-20 shrink-0 rounded px-1.5 py-0.5 text-center text-[11px] font-bold uppercase">{{ $entrada->nivel }}</span>
                        <span class="hidden w-40 shrink-0 font-mono text-xs leading-6 text-slate-500 sm:block">{{ $entrada->fecha }}</span>
                        <span class="min-w-0 flex-1 truncate font-mono text-xs leading-6 text-slate-800" :class="open && 'whitespace-normal break-all'">{{ $entrada->mensaje }}</span>
                        @if ($entrada->detalle !== '')
                            <span class="mt-0.5 shrink-0 text-xs text-slate-400" x-text="open ? 'ocultar' : 'detalle'"></span>
                        @endif
                    </button>
                    @if ($entrada->detalle !== '')
                        <pre x-cloak x-show="open" class="max-h-[32rem] overflow-auto bg-slate-950 px-4 py-3 font-mono text-xs leading-5 text-slate-200">{{ $entrada->detalle }}</pre>
                    @endif
                </div>
            @empty
                <p class="p-12 text-center text-sm text-slate-500">No hay entradas que coincidan con el filtro.</p>
            @endforelse
        </div>

        {{ $entradas->links() }}
    @endif
</div>
