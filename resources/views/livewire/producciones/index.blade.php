@php
    $emojis = ['vacuno' => '🐄', 'porcino' => '🐖', 'aviar' => '🐔'];
    $etiquetas = \App\Http\Controllers\Api\CatalogoController::ETIQUETAS_TIPO;
    $tiposPorId = $tipos->pluck('nombre', 'id');
    $plata = fn ($v) => ((float) $v < 0 ? '-' : '').'$ '.number_format(abs((float) $v), 0, ',', '.');
    $kg = fn ($v) => number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 1, ',', '.');
    $pct = fn ($v) => $v === null ? '—' : number_format((float) $v, 1, ',', '.').'%';
    $estados = ['terminados' => 'Terminadas', 'pendientes' => 'Pendientes', 'todos' => 'Todas'];
    $rindeTotal = $totales->kg_medias > 0 ? $totales->kg_cortes / $totales->kg_medias * 100 : null;
    $margenTotal = $totales->costo > 0 ? $totales->ganancia / $totales->costo * 100 : null;
@endphp
<div>
    <x-ui.page-header title="Producciones" subtitle="Cada desposte con su rinde, costo, venta y ganancia. Los terminados guardan el cálculo del momento en que se terminaron.">
        <div x-data="{ abierto: false }" class="relative" @click.outside="abierto = false">
            <x-ui.button variant="accent" icon="plus" @click="abierto = ! abierto">Nueva producción</x-ui.button>
            <div x-show="abierto" x-cloak x-transition class="absolute right-0 z-20 mt-2 w-52 overflow-hidden rounded-xl border border-stone-200 bg-white py-1 shadow-lg">
                @foreach ($tipos as $tipo)
                    <a href="{{ route('produccion', $tipo->id) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-stone-700 hover:bg-amber-50">
                        <span>{{ $emojis[$tipo->nombre] ?? '🔪' }}</span> {{ $etiquetas[$tipo->nombre] ?? $tipo->nombre }}
                    </a>
                @endforeach
            </div>
        </div>
    </x-ui.page-header>

    {{-- Filtros --}}
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="inline-flex flex-wrap rounded-lg border border-stone-200 bg-white p-1 text-sm shadow-sm">
            @foreach ($periodos as $valor => $texto)
                <button type="button" wire:click="$set('periodo', '{{ $valor }}')" @class([
                    'rounded-md px-3 py-1 font-medium transition',
                    'bg-stone-900 text-white' => $periodo === $valor,
                    'text-stone-600 hover:text-stone-900' => $periodo !== $valor,
                ])>{{ $texto }}</button>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-2">
            <select wire:model.live="filtroTipo" class="rounded-lg border border-stone-300 px-3 py-1.5 text-sm">
                <option value="">Todos los animales</option>
                @foreach ($tipos as $tipo)
                    <option value="{{ $tipo->id }}">{{ $etiquetas[$tipo->nombre] ?? $tipo->nombre }}</option>
                @endforeach
            </select>
            <div class="inline-flex rounded-lg border border-stone-200 bg-stone-50 p-1 text-sm">
                @foreach ($estados as $valor => $texto)
                    <button type="button" wire:click="$set('filtroEstado', '{{ $valor }}')" @class([
                        'rounded-md px-3 py-1 font-medium transition',
                        'bg-white text-stone-900 shadow-sm' => $filtroEstado === $valor,
                        'text-stone-500 hover:text-stone-900' => $filtroEstado !== $valor,
                    ])>{{ $texto }}</button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Resumen del período (terminadas) --}}
    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.stat label="Despostes terminados" :value="$totales->cantidad" emoji="🔪" />
        <x-ui.stat label="Kg producidos" :value="$kg($totales->kg_cortes).' kg'" emoji="⚖️" :hint="'rinde '.$pct($rindeTotal)" />
        <x-ui.stat label="Venta" :value="$plata($totales->venta)" emoji="💰" :hint="'costo '.$plata($totales->costo)" />
        <x-ui.stat :label="$totales->ganancia >= 0 ? 'Ganancia' : 'Pérdida'" :value="$plata($totales->ganancia)" emoji="📈" :hint="'margen '.$pct($margenTotal)" />
    </div>

    <x-ui.card :padding="false">
        @if ($filas->isEmpty())
            <div class="p-5"><x-ui.empty>No hay producciones con estos filtros.</x-ui.empty></div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full whitespace-nowrap text-sm">
                    <thead class="bg-stone-50 text-xs font-semibold uppercase tracking-wide text-stone-500">
                        <tr class="text-right">
                            <th class="px-3 py-2.5 text-left">Fecha</th>
                            <th class="px-3 py-2.5 text-left">Animal</th>
                            <th class="px-3 py-2.5">Unid.</th>
                            <th class="px-3 py-2.5">Kg medias</th>
                            <th class="px-3 py-2.5">Kg cortes</th>
                            <th class="px-3 py-2.5">Rinde</th>
                            <th class="px-3 py-2.5">Merma <span class="block font-normal normal-case text-stone-400">kg y %</span></th>
                            <th class="px-3 py-2.5">Costo <span class="block font-normal normal-case text-stone-400">por kg</span></th>
                            <th class="px-3 py-2.5">Venta <span class="block font-normal normal-case text-stone-400">por kg</span></th>
                            <th class="px-3 py-2.5">Ganancia</th>
                            <th class="px-3 py-2.5">Margen</th>
                            <th class="px-3 py-2.5 text-left">Despostó</th>
                            <th class="px-3 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($filas as $p)
                            @php $pendiente = $p['estado'] === 'pendiente'; @endphp
                            <tr wire:key="prod-{{ $p['id'] }}" @class(['text-right tabular-nums hover:bg-stone-50/60', 'bg-amber-50/50' => $pendiente])>
                                <td class="whitespace-nowrap px-3 py-2.5 text-left">
                                    {{ \Illuminate\Support\Carbon::parse($p['fecha'])->format('d/m/Y') }}
                                    <span class="block text-xs text-stone-400">#{{ $p['id'] }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-left">
                                    {{ $emojis[$tiposPorId[$p['animal_type_id']] ?? ''] ?? '' }} {{ $p['tipo'] }}
                                    @if (($p['modo'] ?? 'musculo') !== 'musculo')<x-ui.badge tone="stone" class="ml-1">{{ \App\Models\Desposte::MODOS[$p['modo']] }}</x-ui.badge>@endif
                                    @if ($pendiente)<x-ui.badge tone="amber" class="ml-1">Pendiente</x-ui.badge>@endif
                                </td>
                                <td class="px-3 py-2.5">{{ $p['medias'] }}</td>
                                <td class="px-3 py-2.5">{{ $kg($p['peso_medias']) }}</td>
                                <td class="px-3 py-2.5">{{ $kg($p['peso_cortes']) }}</td>
                                <td @class(['px-3 py-2.5 font-semibold', 'text-red-700' => $p['rendimiento'] !== null && $p['rendimiento'] < 70])>{{ $pct($p['rendimiento']) }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5">
                                    {{ $kg($p['kg_merma']) }}
                                    <span class="block text-xs text-stone-400">{{ $p['peso_medias'] > 0 ? $pct($p['kg_merma'] / $p['peso_medias'] * 100) : '—' }}</span>
                                </td>
                                <td class="px-3 py-2.5">
                                    {{ $plata($p['costo_total']) }}
                                    <span class="block text-xs text-stone-400">{{ $p['peso_medias'] > 0 ? $plata($p['costo_total'] / $p['peso_medias']) : '—' }}</span>
                                </td>
                                <td class="px-3 py-2.5">
                                    {{ $plata($p['valor_total']) }}
                                    <span class="block text-xs text-stone-400">{{ $p['peso_cortes'] > 0 ? $plata($p['valor_total'] / $p['peso_cortes']) : '—' }}</span>
                                </td>
                                <td @class(['px-3 py-2.5 font-bold', 'text-emerald-700' => $p['diferencia'] >= 0, 'text-red-700' => $p['diferencia'] < 0])>{{ $plata($p['diferencia']) }}</td>
                                <td @class(['px-3 py-2.5', 'text-emerald-700' => ($p['margen'] ?? 0) >= 0, 'text-red-700' => ($p['margen'] ?? 0) < 0])>{{ $pct($p['margen']) }}</td>
                                <td class="max-w-32 truncate px-3 py-2.5 text-left text-stone-600">{{ $p['despostador'] ?: '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5">
                                    @if ($pendiente)
                                        <x-ui.button size="sm" variant="accent" :href="route('produccion', ['tipo' => $p['animal_type_id'], 'desposte' => $p['id']])">Seguir</x-ui.button>
                                    @else
                                        <a href="{{ route('produccion.pdf', $p['id']) }}" class="inline-flex rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 hover:text-stone-700" title="Descargar PDF" aria-label="Descargar PDF del desposte {{ $p['id'] }}">
                                            <x-ui.icon name="download" class="h-4 w-4" />
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    @if ($totales->cantidad > 0)
                        <tfoot class="border-t-2 border-stone-200 bg-stone-50 text-right font-semibold tabular-nums">
                            <tr>
                                <td class="px-3 py-2.5 text-left" colspan="3">Total terminadas ({{ $totales->cantidad }})</td>
                                <td class="px-3 py-2.5">{{ $kg($totales->kg_medias) }}</td>
                                <td class="px-3 py-2.5">{{ $kg($totales->kg_cortes) }}</td>
                                <td class="px-3 py-2.5">{{ $pct($rindeTotal) }}</td>
                                <td class="px-3 py-2.5">{{ $kg($totales->kg_merma) }}</td>
                                <td class="px-3 py-2.5">
                                    {{ $plata($totales->costo) }}
                                    <span class="block text-xs font-normal text-stone-400">{{ $totales->kg_medias > 0 ? $plata($totales->costo / $totales->kg_medias) : '—' }}</span>
                                </td>
                                <td class="px-3 py-2.5">
                                    {{ $plata($totales->venta) }}
                                    <span class="block text-xs font-normal text-stone-400">{{ $totales->kg_cortes > 0 ? $plata($totales->venta / $totales->kg_cortes) : '—' }}</span>
                                </td>
                                <td @class(['px-3 py-2.5', 'text-emerald-700' => $totales->ganancia >= 0, 'text-red-700' => $totales->ganancia < 0])>{{ $plata($totales->ganancia) }}</td>
                                <td class="px-3 py-2.5">{{ $pct($margenTotal) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @if ($producciones->hasPages())
                <div class="border-t border-stone-100 px-5 py-3">{{ $producciones->links() }}</div>
            @endif
        @endif
    </x-ui.card>

    <p class="mt-3 text-xs text-stone-500">En un desposte por piezas grandes la venta se calcula con el precio de cada pieza; si después se cuartea, el cuarteo tiene su propio rinde y ganancia (con la pieza a su costo). Margen = ganancia sobre el costo. Los pendientes se calculan con lo cargado hasta ahora y los precios actuales; no suman en los totales.</p>
</div>
