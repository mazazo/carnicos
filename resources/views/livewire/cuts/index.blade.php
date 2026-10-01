@php
    $emojis = ['vacuno' => '🐄', 'porcino' => '🐖', 'aviar' => '🐔'];
    $etiquetas = ['vacuno' => 'Vacuno', 'porcino' => 'Cerdo', 'aviar' => 'Avícola'];
    $filtros = ['todos' => 'Todos', 'habilitados' => 'Habilitados', 'deshabilitados' => 'Deshabilitados', 'piezas' => 'Piezas grandes', 'propios' => 'Propios'];
    $tipoActual = $tipos->firstWhere('id', $tipoId);
@endphp
<div>
    <x-ui.page-header title="Cortes" subtitle="Elegí qué cortes usa tu carnicería en cada animal y sumá los tuyos con los nombres que usan en tu zona.">
        @if ($puedeEditar && $tipoActual)
            <x-ui.button variant="accent" icon="plus" wire:click="$set('agregando', true)">Agregar corte</x-ui.button>
        @endif
    </x-ui.page-header>

    @if (session('success'))
        <x-ui.alert class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif

    @if ($tipos->isEmpty())
        <x-ui.empty>Tu carnicería no tiene tipos de animal habilitados.</x-ui.empty>
    @else
        {{-- Tipos de animal --}}
        <div class="mb-4 grid grid-cols-3 gap-2 sm:gap-3">
            @foreach ($tipos as $tipo)
                @php($activo = $tipo->id === $tipoId)
                <button
                    type="button"
                    wire:click="elegirTipo({{ $tipo->id }})"
                    @class([
                        'flex flex-col items-center gap-2 rounded-2xl border p-3 text-center transition sm:flex-row sm:gap-3 sm:p-4 sm:text-left',
                        'border-amber-500 bg-amber-50 ring-1 ring-amber-500' => $activo,
                        'border-stone-200 bg-white hover:border-stone-300' => ! $activo,
                    ])
                >
                    <span @class(['flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-xl', 'bg-amber-100' => $activo, 'bg-stone-100' => ! $activo])>{{ $emojis[$tipo->nombre] ?? '🔪' }}</span>
                    <span class="min-w-0">
                        <span class="block font-semibold text-stone-900">{{ $etiquetas[$tipo->nombre] ?? ucfirst($tipo->nombre) }}</span>
                        <span class="block text-xs text-stone-500">{{ $conteos[$tipo->id]['habilitados'] }} de {{ $conteos[$tipo->id]['total'] }}<span class="hidden sm:inline"> cortes habilitados</span></span>
                    </span>
                </button>
            @endforeach
        </div>

        <x-ui.card :padding="false">
            {{-- Agregar corte --}}
            @if ($agregando && $puedeEditar)
                <form wire:submit="agregar" class="border-b border-stone-100 bg-amber-50/50 px-5 py-4">
                    <label for="nuevo-corte" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">
                        Nuevo corte de {{ $etiquetas[$tipoActual->nombre] ?? $tipoActual->nombre }}
                    </label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input id="nuevo-corte" wire:model="nuevoNombre" type="text" maxlength="120" autofocus placeholder="Ej: Tapa de asado, Palomita, Chingolo…" class="w-full flex-1 rounded-lg border border-stone-300 px-3 py-2 text-sm">
                        <select wire:model="nuevoNivel" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Tipo de corte">
                            <option value="musculo">Músculo</option>
                            <option value="primario">Pieza grande</option>
                        </select>
                        <div class="flex gap-2">
                            <x-ui.button type="submit" variant="primary" icon="check">Agregar</x-ui.button>
                            <x-ui.button variant="ghost" wire:click="$set('agregando', false)">Cancelar</x-ui.button>
                        </div>
                    </div>
                    @error('nuevoNombre')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-stone-500">Si el corte ya está en la lista pero deshabilitado, se habilita en vez de duplicarlo.</p>
                </form>
            @endif

            {{-- Búsqueda y filtros --}}
            <div class="flex flex-col gap-3 border-b border-stone-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="relative lg:w-72">
                    <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar corte" class="w-full rounded-lg border border-stone-300 py-2 pl-9 pr-3 text-sm">
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex flex-wrap rounded-lg border border-stone-200 bg-stone-50 p-1 text-sm">
                        @foreach ($filtros as $valor => $texto)
                            <button type="button" wire:click="$set('filtro', '{{ $valor }}')" @class([
                                'rounded-md px-3 py-1 font-medium transition',
                                'bg-white text-stone-900 shadow-sm' => $filtro === $valor,
                                'text-stone-500 hover:text-stone-900' => $filtro !== $valor,
                            ])>{{ $texto }}</button>
                        @endforeach
                    </div>
                    @if ($puedeEditar && $cortes->isNotEmpty())
                        <x-ui.button size="sm" variant="secondary" wire:click="habilitarTodos(true)" wire:confirm="¿Habilitar todos los cortes de la lista?">Habilitar todos</x-ui.button>
                        <x-ui.button size="sm" variant="ghost" wire:click="habilitarTodos(false)" wire:confirm="¿Deshabilitar todos los cortes de la lista?">Deshabilitar todos</x-ui.button>
                    @endif
                </div>
            </div>

            {{-- Lista --}}
            @if ($cortes->isEmpty())
                <div class="p-5">
                    <x-ui.empty>{{ $search !== '' ? 'Ningún corte coincide con la búsqueda.' : 'No hay cortes en esta lista.' }}</x-ui.empty>
                </div>
            @else
                <ul class="divide-y divide-stone-100">
                    @foreach ($cortes as $corte)
                        <li wire:key="corte-{{ $corte->id }}" @class(['flex items-center gap-3 px-5 py-3', 'bg-stone-50/70' => ! $corte->habilitado])>
                            @if ($editandoId === $corte->id)
                                <form wire:submit="guardarNombre" class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                                    <div class="flex-1">
                                        <input wire:model="editandoNombre" type="text" maxlength="120" autofocus class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-sm">
                                        @error('editandoNombre')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="flex gap-2">
                                        <x-ui.button type="submit" size="sm" variant="primary">Guardar</x-ui.button>
                                        <x-ui.button size="sm" variant="ghost" wire:click="cancelarEdicion">Cancelar</x-ui.button>
                                    </div>
                                </form>
                            @else
                                <div class="min-w-0 flex-1">
                                    <p @class(['truncate font-medium first-letter:uppercase', 'text-stone-900' => $corte->habilitado, 'text-stone-400 line-through decoration-stone-300' => ! $corte->habilitado])>{{ $corte->nombre_canonico }}</p>
                                    @if ($corte->esPrimario() || $corte->esPropio())
                                        <div class="mt-0.5 flex flex-wrap gap-1">
                                            @if ($corte->esPrimario())<x-ui.badge tone="stone">Pieza grande</x-ui.badge>@endif
                                            @if ($corte->esPropio())<x-ui.badge tone="amber">Propio</x-ui.badge>@endif
                                        </div>
                                    @endif
                                    @if ($corte->esPrimario() && $corte->partes->isNotEmpty())
                                        <p class="mt-0.5 truncate text-xs text-stone-500">Contiene: {{ $corte->partes->pluck('nombre_canonico')->map(fn ($n) => mb_strtolower($n))->unique()->implode(', ') }}</p>
                                    @endif
                                </div>

                                @if ($puedeEditar && $corte->esPropio())
                                    <button type="button" wire:click="editar({{ $corte->id }})" class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-stone-700" title="Cambiar nombre" aria-label="Cambiar nombre de {{ $corte->nombre_canonico }}">
                                        <x-ui.icon name="pencil" class="h-4 w-4" />
                                    </button>
                                    <button type="button" wire:click="eliminar({{ $corte->id }})" wire:confirm="¿Borrar &quot;{{ $corte->nombre_canonico }}&quot;? Si ya se usó en despostes, solo se deshabilita." class="rounded-lg p-2 text-stone-400 hover:bg-red-50 hover:text-red-700" title="Borrar" aria-label="Borrar {{ $corte->nombre_canonico }}">
                                        <x-ui.icon name="trash" class="h-4 w-4" />
                                    </button>
                                @endif

                                {{-- Interruptor habilitado --}}
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked="{{ $corte->habilitado ? 'true' : 'false' }}"
                                    aria-label="{{ $corte->habilitado ? 'Deshabilitar' : 'Habilitar' }} {{ $corte->nombre_canonico }}"
                                    @if ($puedeEditar) wire:click="alternar({{ $corte->id }})" @else disabled @endif
                                    @class([
                                        'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition disabled:cursor-not-allowed disabled:opacity-60',
                                        'bg-amber-500' => $corte->habilitado,
                                        'bg-stone-300' => ! $corte->habilitado,
                                    ])
                                >
                                    <span @class(['inline-block h-5 w-5 rounded-full bg-white shadow transition', 'translate-x-5' => $corte->habilitado, 'translate-x-0.5' => ! $corte->habilitado])></span>
                                </button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        @unless ($puedeEditar)
            <p class="mt-3 text-sm text-stone-500">Solo el dueño de la carnicería puede cambiar los cortes.</p>
        @endunless
    @endif
</div>
