@php
    $emojis = ['vacuno' => '🐄', 'porcino' => '🐖', 'aviar' => '🐔'];
    $etiquetas = \App\Http\Controllers\Api\CatalogoController::ETIQUETAS_TIPO;
    $plata = fn ($v) => ((float) $v < 0 ? '-' : '').'$ '.number_format(abs((float) $v), 0, ',', '.');
    $kg = fn ($v) => number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 2, ',', '.').' kg';
    $estados = ['todos' => 'Todos', 'disponible' => 'Disponibles', 'en_desposte' => 'En desposte', 'despostada' => 'Despostados'];
    $badge = [
        'disponible' => ['Disponible', 'green'],
        'en_desposte' => ['En desposte', 'amber'],
        'despostada' => ['Despostado', 'stone'],
    ];
    $nombresFormato = ['res' => 'Res entera', 'media_res' => 'Media res', 'cajon' => 'Cajón', 'pieza' => 'Pieza grande'];
    $campo = 'w-full rounded-lg border border-stone-300 px-3 py-2 text-sm';
    $etiqueta = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500';
@endphp
<div>
    <x-ui.page-header title="Ingresos" subtitle="Lo que entra a la carnicería: reses, medias y cajones. Lo disponible se usa en las producciones.">
        <x-ui.button variant="accent" icon="plus" wire:click="abrirNuevo">Nuevo ingreso</x-ui.button>
    </x-ui.page-header>

    @if (session('success'))
        <x-ui.alert class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif

    {{-- Nuevo ingreso --}}
    @if ($nuevo)
        <x-ui.card title="Nuevo ingreso" subtitle="Queda disponible para producir." class="mb-6">
            <x-slot:actions>
                <button type="button" wire:click="$set('nuevo', false)" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 hover:text-stone-700" aria-label="Cerrar">
                    <x-ui.icon name="x" />
                </button>
            </x-slot:actions>

            <form wire:submit="guardar" class="space-y-4">
                <div>
                    <span class="{{ $etiqueta }}">Animal</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tipos as $tipo)
                            <button type="button" wire:click="$set('tipoId', {{ $tipo->id }})" @class([
                                'inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium transition',
                                'border-amber-500 bg-amber-50 text-amber-900 ring-1 ring-amber-500' => $tipoId === $tipo->id,
                                'border-stone-200 bg-white text-stone-600 hover:border-stone-300' => $tipoId !== $tipo->id,
                            ])>{{ $emojis[$tipo->nombre] ?? '🔪' }} {{ $etiquetas[$tipo->nombre] ?? ucfirst($tipo->nombre) }}</button>
                        @endforeach
                    </div>
                    @error('tipoId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="{{ $etiqueta }}" for="formato">Formato</label>
                        <select id="formato" wire:model.live="formatoElegido" class="{{ $campo }}">
                            @foreach ($formatos as $f)
                                @continue($f === 'pieza')
                                <option value="{{ $f }}">{{ $nombresFormato[$f] ?? $f }}</option>
                            @endforeach
                            @if ($piezas->isNotEmpty())
                                <optgroup label="Piezas grandes">
                                    @foreach ($piezas as $p)
                                        <option value="pieza:{{ $p->id }}">{{ $p->nombre_canonico }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        @error('formato')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('piezaId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $etiqueta }}" for="cantidad">Cantidad</label>
                        <input id="cantidad" wire:model="cantidad" type="number" min="1" max="999" class="{{ $campo }}">
                        @error('cantidad')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $etiqueta }}" for="peso">Kg totales</label>
                        <input id="peso" wire:model.live.debounce.400ms="peso" type="text" inputmode="decimal" placeholder="0" class="{{ $campo }} text-right tabular-nums">
                        @error('peso')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $etiqueta }}" for="precio">Precio por kg</label>
                        <input id="precio" wire:model.live.debounce.400ms="precio" type="text" inputmode="decimal" placeholder="$ 0" class="{{ $campo }} text-right tabular-nums">
                        @error('precio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $etiqueta }}" for="fecha">Fecha</label>
                        <input id="fecha" wire:model="fecha" type="date" max="{{ today()->toDateString() }}" class="{{ $campo }}">
                        @error('fecha')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $etiqueta }}" for="proveedor">Proveedor</label>
                        <input id="proveedor" wire:model="proveedor" type="text" maxlength="150" class="{{ $campo }}">
                    </div>
                    <div>
                        <label class="{{ $etiqueta }}" for="frigorifico">Frigorífico</label>
                        <input id="frigorifico" wire:model="frigorifico" type="text" maxlength="150" class="{{ $campo }}">
                    </div>
                    <div class="rounded-xl bg-stone-50 px-4 py-2">
                        <span class="{{ $etiqueta }}">Costo total</span>
                        <p class="text-xl font-bold tabular-nums text-stone-900">{{ $plata($costoNuevo) }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-stone-100 pt-4">
                    <x-ui.button variant="ghost" wire:click="$set('nuevo', false)">Cancelar</x-ui.button>
                    <x-ui.button type="submit" variant="primary" icon="check">Guardar ingreso</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    {{-- Disponibles por animal --}}
    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach ($tipos as $tipo)
            @php $d = $disponibles->get($tipo->id); @endphp
            <a href="{{ route('produccion', $tipo->id) }}" class="group flex items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm transition hover:border-amber-400">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xl">{{ $emojis[$tipo->nombre] ?? '🔪' }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block text-xs font-medium uppercase tracking-wide text-stone-500">{{ $etiquetas[$tipo->nombre] ?? $tipo->nombre }} disponible</span>
                    <span class="block text-lg font-bold tabular-nums text-stone-900">{{ $d ? $d->unidades.' · '.$kg($d->kg) : 'Nada' }}</span>
                    <span class="block text-xs font-semibold text-amber-700">{{ $d ? $plata($d->costo).' · Producir →' : 'Producir →' }}</span>
                </span>
            </a>
        @endforeach
    </div>

    {{-- Listado --}}
    <x-ui.card :padding="false">
        <div class="flex flex-col gap-3 border-b border-stone-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap gap-2">
                <select wire:model.live="filtroTipo" class="rounded-lg border border-stone-300 px-3 py-1.5 text-sm">
                    <option value="">Todos los animales</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo->id }}">{{ $etiquetas[$tipo->nombre] ?? $tipo->nombre }}</option>
                    @endforeach
                </select>
                <div class="inline-flex flex-wrap rounded-lg border border-stone-200 bg-stone-50 p-1 text-sm">
                    @foreach ($estados as $valor => $texto)
                        <button type="button" wire:click="$set('filtroEstado', '{{ $valor }}')" @class([
                            'rounded-md px-3 py-1 font-medium transition',
                            'bg-white text-stone-900 shadow-sm' => $filtroEstado === $valor,
                            'text-stone-500 hover:text-stone-900' => $filtroEstado !== $valor,
                        ])>{{ $texto }}</button>
                    @endforeach
                </div>
            </div>
            <div class="relative lg:w-64">
                <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Proveedor o frigorífico" class="w-full rounded-lg border border-stone-300 py-2 pl-9 pr-3 text-sm">
            </div>
        </div>

        @if ($ingresos->isEmpty())
            <div class="p-5"><x-ui.empty emoji="🐄">No hay ingresos con estos filtros.</x-ui.empty></div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full whitespace-nowrap text-sm">
                    <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-4 py-2.5">Ingreso</th>
                            <th class="px-4 py-2.5">Animal</th>
                            <th class="px-4 py-2.5">Formato</th>
                            <th class="px-4 py-2.5 text-right">Kg</th>
                            <th class="px-4 py-2.5 text-right">$/kg</th>
                            <th class="px-4 py-2.5 text-right">Costo</th>
                            <th class="px-4 py-2.5">Proveedor / frigorífico</th>
                            <th class="px-4 py-2.5">Estado</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($ingresos as $i)
                            <tr wire:key="ingreso-{{ $i->id }}" class="hover:bg-stone-50/60">
                                <td class="whitespace-nowrap px-4 py-2.5 tabular-nums">
                                    {{ $i->fecha?->format('d/m/Y') }}
                                    @if ($i->enStock())
                                        <div class="mt-0.5"><x-ui.dias :animal="$i" /></div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5">{{ $emojis[$i->animalType?->nombre] ?? '' }} {{ $etiquetas[$i->animalType?->nombre] ?? $i->animalType?->nombre }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5">{{ $i->cantidad > 1 ? $i->cantidad.' × ' : '' }}{{ $i->etiquetaFormato() ?: '—' }} <span class="text-stone-400">#{{ $i->id }}</span></td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums">{{ $kg($i->peso_total) }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums">{{ $plata($i->precio_kg) }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-right font-semibold tabular-nums">{{ $plata($i->costoTotal()) }}</td>
                                <td class="max-w-56 truncate px-4 py-2.5 text-stone-600">{{ collect([$i->proveedor, $i->frigorifico])->filter()->implode(' · ') ?: '—' }}</td>
                                <td class="px-4 py-2.5">
                                    @php $b = $badge[$i->estado] ?? [$i->estado, 'stone']; @endphp
                                    <x-ui.badge :tone="$b[1]">{{ $b[0] }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    @if ($i->estado === \App\Models\Animal::DISPONIBLE && ! $usados->has($i->id))
                                        <button type="button" wire:click="eliminar({{ $i->id }})" wire:confirm="¿Eliminar este ingreso? Usalo solo para corregir un error de carga." class="rounded-lg p-1.5 text-stone-400 hover:bg-red-50 hover:text-red-700" title="Eliminar" aria-label="Eliminar ingreso {{ $i->id }}">
                                            <x-ui.icon name="trash" class="h-4 w-4" />
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-stone-200 bg-stone-50 font-semibold">
                        <tr>
                            <td class="px-4 py-2.5" colspan="3">Total ({{ $totales->unidades }})</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ $kg($totales->kg) }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-stone-500">{{ $totales->kg > 0 ? $plata($totales->costo / $totales->kg) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ $plata($totales->costo) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @if ($ingresos->hasPages())
                <div class="border-t border-stone-100 px-5 py-3">{{ $ingresos->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
