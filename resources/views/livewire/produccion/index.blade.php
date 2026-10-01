@php
    $emoji = ['vacuno' => '🐄', 'porcino' => '🐖', 'aviar' => '🐔'][$tipo->nombre] ?? '🔪';
    $etiqueta = \App\Http\Controllers\Api\CatalogoController::ETIQUETAS_TIPO[$tipo->nombre] ?? ucfirst($tipo->nombre);
    $esCajon = $tipo->modalidad === 'cajon';
    $plata = fn ($v) => ((float) $v < 0 ? '-' : '').'$ '.number_format(abs((float) $v), 0, ',', '.');
    $kg = fn ($v) => number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 2, ',', '.').' kg';
    $pct = fn ($v) => number_format((float) $v, 1, ',', '.').'%';
@endphp
<div>
    <a href="{{ route('producciones.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium text-stone-500 hover:text-stone-900 print:hidden">
        <x-ui.icon name="chevron-right" class="h-4 w-4 rotate-180" /> Producciones
    </a>

    <x-ui.page-header :title="'Producción · '.$etiqueta" :subtitle="match ($paso) {
        'medias' => 'Elegí los ingresos a despostar o seguí un desposte pendiente.',
        'cortes' => 'Cargá los kg de cada corte. Todo queda guardado mientras el desposte está pendiente.',
        default => 'Desposte terminado.',
    }">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-2xl">{{ $emoji }}</span>
    </x-ui.page-header>

    @if (session('aviso'))
        <x-ui.alert tone="amber" class="mb-4">{{ session('aviso') }}</x-ui.alert>
    @endif

    {{-- ===================== Paso 1: elegir ingresos ===================== --}}
    @if ($paso === 'medias')
        @if (count($modos) > 1)
            @php
                $ayudaModo = [
                    'musculo' => 'De la media o res salen los cortes del mostrador: asado, nalga, peceto…',
                    'primario' => 'De la media o res salen piezas grandes (Mocho, Parrillero, Paleta entera…) que quedan disponibles para cuartear.',
                    'cuarteo' => 'Elegís una pieza grande y la despostás en sus músculos.',
                ];
            @endphp
            <div class="mb-6">
                <p class="mb-2 text-sm font-semibold text-stone-700">¿Cómo vas a despostar?</p>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($modos as $valor => $texto)
                        <button type="button" wire:click="elegirModo('{{ $valor }}')" @class([
                            'rounded-2xl border p-4 text-left transition',
                            'border-amber-500 bg-amber-50 ring-1 ring-amber-500' => $modo === $valor,
                            'border-stone-200 bg-white hover:border-stone-300' => $modo !== $valor,
                        ])>
                            <span class="block font-semibold text-stone-900">{{ $texto }}</span>
                            <span class="mt-0.5 block text-xs text-stone-500">{{ $ayudaModo[$valor] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
        @if ($pendientes->isNotEmpty())
            <x-ui.card title="Despostes pendientes" subtitle="Quedaron a medio cargar (desde la web o la app)." class="mb-6" :padding="false">
                <ul class="divide-y divide-stone-100">
                    @foreach ($pendientes as $p)
                        <li wire:key="pend-{{ $p->id }}" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800"><x-ui.icon name="clock" /></span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-stone-900">
                                        {{ $p->modo === 'cuarteo' ? $p->animales->map(fn ($a) => $a->etiquetaFormato())->implode(', ') : $p->animales->count().' '.($p->animales->count() === 1 ? 'unidad' : 'unidades') }} · {{ $kg($p->peso_medias) }}
                                        @if ($p->modo !== 'musculo')<x-ui.badge tone="amber" class="ml-1">{{ \App\Models\Desposte::MODOS[$p->modo] }}</x-ui.badge>@endif
                                    </p>
                                    <p class="text-xs text-stone-500">Cargado {{ $kg($p->pesadas->sum('peso')) }} · desde {{ $p->fecha_desposte?->format('d/m/Y') }}{{ $p->despostador_nombre ? ' · '.$p->despostador_nombre : '' }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <x-ui.button size="sm" variant="danger" icon="trash" wire:click="descartar({{ $p->id }})" wire:confirm="¿Descartar este desposte? Se borra lo cargado y las medias vuelven a disponibles.">Descartar</x-ui.button>
                                <x-ui.button size="sm" variant="primary" wire:click="seguir({{ $p->id }})">Seguir</x-ui.button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endif

        <x-ui.card :title="$modo === 'cuarteo' ? 'Piezas grandes disponibles' : ($esCajon ? 'Cajones' : 'Ingresos').' disponibles'" :padding="false">
            <x-slot:actions>
                <x-ui.button size="sm" variant="ghost" icon="plus" :href="route('ingresos.index', ['nuevo' => 1, 'tipo' => $tipo->id])">Nuevo ingreso</x-ui.button>
            </x-slot:actions>

            @if ($disponibles->isEmpty())
                <div class="p-5"><x-ui.empty :emoji="$emoji">No hay {{ $esCajon ? 'cajones' : 'medias' }} sin despostar. Cargá un ingreso primero.</x-ui.empty></div>
            @else
                <ul class="divide-y divide-stone-100">
                    @foreach ($disponibles as $m)
                        @php $elegida = in_array($m->id, $seleccion, true); @endphp
                        <li wire:key="media-{{ $m->id }}">
                            <button type="button" wire:click="alternarMedia({{ $m->id }})" @class(['flex w-full items-center gap-4 px-5 py-3 text-left transition', 'bg-amber-50' => $elegida, 'hover:bg-stone-50' => ! $elegida])>
                                <span @class(['flex h-5 w-5 shrink-0 items-center justify-center rounded border', 'border-amber-500 bg-amber-500 text-white' => $elegida, 'border-stone-300 bg-white' => ! $elegida])>
                                    @if ($elegida)<x-ui.icon name="check" class="h-3.5 w-3.5" />@endif
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-stone-900">{{ $m->cantidad > 1 ? $m->cantidad.' × ' : '' }}{{ $m->etiquetaFormato() }} <span class="text-stone-400">#{{ $m->id }}</span> <x-ui.dias :animal="$m" class="ml-1 align-middle" /></p>
                                    <p class="truncate text-xs text-stone-500">{{ $m->fecha?->format('d/m/Y') }}{{ collect([$m->proveedor, $m->frigorifico])->filter()->map(fn ($x) => ' · '.$x)->implode('') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold tabular-nums text-stone-900">{{ $kg($m->peso_total) }}</p>
                                    <p class="text-xs tabular-nums text-stone-500">{{ $plata($m->precio_kg) }}/kg · {{ $plata($m->costoTotal()) }}</p>
                                </div>
                            </button>
                        </li>
                    @endforeach
                </ul>

                @php $elegidas = $disponibles->whereIn('id', $seleccion); @endphp
                <div class="sticky bottom-0 flex flex-col gap-3 rounded-b-2xl border-t border-stone-200 bg-white/95 px-5 py-4 backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex gap-6 text-sm">
                        <div><p class="text-xs text-stone-500">Elegidas</p><p class="font-bold tabular-nums">{{ $elegidas->count() }}</p></div>
                        <div><p class="text-xs text-stone-500">Kg</p><p class="font-bold tabular-nums">{{ $kg($elegidas->sum('peso_total')) }}</p></div>
                        <div><p class="text-xs text-stone-500">Costo</p><p class="font-bold tabular-nums">{{ $plata($elegidas->sum(fn ($m) => $m->costoTotal())) }}</p></div>
                    </div>
                    <x-ui.button variant="accent" icon="chevron-right" wire:click="iniciar" :disabled="$elegidas->isEmpty()">{{ $modo === 'cuarteo' ? 'Iniciar cuarteo' : 'Iniciar desposte' }}</x-ui.button>
                </div>
                @error('animal_ids')<p class="px-5 pb-4 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('animal_type_id')<p class="px-5 pb-4 text-sm text-red-600">{{ $message }}</p>@enderror
            @endif
        </x-ui.card>
    @endif

    {{-- ===================== Paso 2: cortes ===================== --}}
    @if ($paso === 'cortes')
        @php
            $rinde = $kgMedias > 0 ? $kgCortes / $kgMedias * 100 : 0;
            $diferencia = $venta - $costo;
        @endphp

        {{-- Medias del desposte --}}
        <div class="mb-4 flex flex-col gap-3 rounded-2xl bg-stone-900 p-5 text-white sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-400">{{ $desposte->modo === 'cuarteo' ? 'Cuarteo' : 'Desposte' }} pendiente #{{ $desposte->id }}{{ $desposte->modo === 'primario' ? ' · por piezas grandes' : '' }}</p>
                <p class="mt-1 text-lg font-semibold">{{ $desposte->animales->count() }} {{ $desposte->animales->count() === 1 ? 'unidad' : 'unidades' }} · {{ $kg($kgMedias) }} · costo {{ $plata($costo) }}</p>
                <p class="text-sm text-stone-400">{{ $desposte->animales->map(fn ($a) => $a->etiquetaFormato().' #'.$a->id.' ('.$kg($a->pivot->peso).')')->implode(' · ') }}</p>
            </div>
            <div class="text-sm text-stone-400 sm:text-right">
                <p>Costo por kg: <span class="font-semibold text-white">{{ $plata($kgMedias > 0 ? $costo / $kgMedias : 0) }}</span></p>
                <p wire:loading.remove class="text-emerald-400">✓ Guardado</p>
                <p wire:loading style="display: none" class="text-amber-400">Guardando…</p>
            </div>
        </div>

        <x-ui.card :padding="false">
            <div class="flex flex-col gap-3 border-b border-stone-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative sm:w-72">
                    <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar corte" class="w-full rounded-lg border border-stone-300 py-2 pl-9 pr-3 text-sm">
                </div>
                <div class="flex flex-wrap gap-4">
                    @if ($esCuarteo)
                        <label class="inline-flex items-center gap-2 text-sm text-stone-600">
                            <input type="checkbox" wire:model.live="soloDeLaPieza" class="rounded border-stone-300 text-amber-500 focus:ring-amber-500">
                            Solo músculos de la pieza
                        </label>
                    @endif
                    <label class="inline-flex items-center gap-2 text-sm text-stone-600">
                        <input type="checkbox" wire:model.live="soloCargados" class="rounded border-stone-300 text-amber-500 focus:ring-amber-500">
                        Solo cortes cargados ({{ $cargados }})
                    </label>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-4 py-2.5">{{ $desposte->modo === 'primario' ? 'Pieza grande' : 'Corte' }}</th>
                            <th class="px-4 py-2.5">Peso (kg)</th>
                            <th class="px-4 py-2.5">Precio por kg</th>
                            <th class="px-4 py-2.5 text-right">Subtotal</th>
                            <th class="px-4 py-2.5 text-right">% kg</th>
                            <th class="px-4 py-2.5 text-right">% $</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($cortes as $corte)
                            @php
                                $kgCorte = $numero($pesos[$corte->id] ?? '');
                                $precio = $numero($precios[$corte->id] ?? '');
                                $subtotal = $kgCorte * $precio;
                            @endphp
                            <tr wire:key="corte-{{ $corte->id }}" @class(['bg-amber-50/40' => $kgCorte > 0])>
                                <td class="px-4 py-2 font-medium text-stone-900 first-letter:uppercase">{{ $corte->nombre_canonico }}</td>
                                <td class="px-4 py-2">
                                    <input wire:model.blur="pesos.{{ $corte->id }}" type="text" inputmode="decimal" placeholder="0" class="w-28 rounded-md border border-stone-300 px-2 py-1.5 text-right text-sm tabular-nums">
                                    @error('pesos.'.$corte->id)<p class="mt-0.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </td>
                                <td class="px-4 py-2">
                                    @if ($esDueno)
                                        <input wire:model.blur="precios.{{ $corte->id }}" type="text" inputmode="decimal" placeholder="Sin precio" class="w-28 rounded-md border border-stone-300 px-2 py-1.5 text-right text-sm tabular-nums">
                                        @error('precios.'.$corte->id)<p class="mt-0.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                    @else
                                        <span class="tabular-nums text-stone-600">{{ $precio > 0 ? $plata($precio) : 'Sin precio' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right font-semibold tabular-nums text-stone-900">{{ $subtotal > 0 ? $plata($subtotal) : '—' }}</td>
                                <td class="px-4 py-2 text-right text-xs tabular-nums text-stone-600">{{ $kgCorte > 0 && $kgMedias > 0 ? $pct($kgCorte / $kgMedias * 100) : '—' }}</td>
                                <td class="px-4 py-2 text-right text-xs tabular-nums text-stone-600">{{ $subtotal > 0 && $costo > 0 ? $pct($subtotal / $costo * 100) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-stone-500">No hay cortes para mostrar. Revisá la búsqueda o habilitá cortes en <a href="{{ route('cuts.index', ['tipo' => $tipo->id]) }}" class="font-medium text-amber-700 underline">Cortes</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        @unless ($esDueno)
            <p class="mt-2 text-xs text-stone-500">Los precios los cambia el dueño. Al terminar se usan los precios vigentes.</p>
        @endunless

        {{-- Totales y acciones: fijos abajo en pantallas medianas, siempre a la vista mientras se carga --}}
        <div class="mt-4 md:sticky md:bottom-0 md:z-10 md:-mx-2 md:rounded-t-2xl md:bg-stone-100/95 md:px-2 md:pb-3 md:pt-2 md:backdrop-blur">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7">
            <div class="rounded-xl border border-stone-200 bg-white px-4 py-3"><p class="text-xs font-medium uppercase tracking-wide text-stone-500">Kg cortes</p><p class="mt-1 text-lg font-bold tabular-nums">{{ $kg($kgCortes) }}</p></div>
            <div @class(['rounded-xl border px-4 py-3', 'border-red-200 bg-red-50' => $kgCortes > 0 && $rinde < 70, 'border-stone-200 bg-white' => ! ($kgCortes > 0 && $rinde < 70)])><p class="text-xs font-medium uppercase tracking-wide text-stone-500">Rinde</p><p class="mt-1 text-lg font-bold tabular-nums">{{ $pct($rinde) }}</p></div>
            <div class="rounded-xl border border-stone-200 bg-white px-4 py-3"><p class="text-xs font-medium uppercase tracking-wide text-stone-500">Merma</p><p class="mt-1 text-lg font-bold tabular-nums">{{ $kg(max(0, $kgMedias - $kgCortes)) }}</p></div>
            <div class="rounded-xl border border-stone-200 bg-white px-4 py-3"><p class="text-xs font-medium uppercase tracking-wide text-stone-500">Costo</p><p class="mt-1 text-lg font-bold tabular-nums">{{ $plata($costo) }}</p></div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3"><p class="text-xs font-medium uppercase tracking-wide text-amber-800">Venta</p><p class="mt-1 text-lg font-bold tabular-nums text-amber-950">{{ $plata($venta) }}</p></div>
            <div @class(['rounded-xl border px-4 py-3', 'border-emerald-200 bg-emerald-50' => $diferencia >= 0, 'border-red-200 bg-red-50' => $diferencia < 0])><p class="text-xs font-medium uppercase tracking-wide text-stone-500">{{ $diferencia >= 0 ? 'Ganancia' : 'Pérdida' }}</p><p @class(['mt-1 text-lg font-bold tabular-nums', 'text-emerald-800' => $diferencia >= 0, 'text-red-700' => $diferencia < 0])>{{ $plata($diferencia) }}</p></div>
            <div class="rounded-xl border border-stone-200 bg-white px-4 py-3"><p class="text-xs font-medium uppercase tracking-wide text-stone-500">Margen</p><p class="mt-1 text-lg font-bold tabular-nums">{{ $costo > 0 ? $pct($diferencia / $costo * 100) : '—' }}</p></div>
        </div>

        <div class="mt-2 space-y-2">
            @if ($kgCortes > 0 && $rinde < 70)
                <x-ui.alert tone="red">El rendimiento está por debajo del 70%.</x-ui.alert>
            @endif
            @if ($kgCortes > 0 && $diferencia < 0)
                <x-ui.alert tone="amber">El valor de venta no cubre el costo: faltan {{ $plata(-$diferencia) }}. {{ $esDueno ? 'Revisá los precios por kg antes de terminar.' : 'Avisale al dueño para revisar los precios.' }}</x-ui.alert>
            @endif
            @error('terminar')<x-ui.alert tone="red">{{ $message }}</x-ui.alert>@enderror
        </div>

        <div class="mt-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-ui.button variant="danger" icon="trash" wire:click="cancelar" wire:confirm="¿Cancelar el desposte? Se borra lo cargado y las medias vuelven a disponibles.">Cancelar desposte</x-ui.button>
            <div class="flex flex-col gap-2 sm:flex-row">
                <x-ui.button variant="secondary" :href="route('dashboard')">Seguir después</x-ui.button>
                <x-ui.button variant="secondary" icon="download" wire:click="terminarConPdf" wire:confirm="¿Terminar el desposte y descargar el PDF? Los cortes se guardan con el precio actual y las medias quedan despostadas.">Terminar y PDF</x-ui.button>
                <x-ui.button variant="primary" icon="check" wire:click="terminar" wire:confirm="¿Terminar el desposte? Los cortes se guardan con el precio actual y las medias quedan despostadas.">Terminar desposte</x-ui.button>
            </div>
        </div>
        </div>
    @endif

    {{-- ===================== Paso 3: resumen ===================== --}}
    @if ($paso === 'resumen')
        @php
            $r = $resumen;
            $lineas = collect($r['cortes'])->map(fn ($c) => '• '.$c['nombre'].': '.$kg($c['peso']).($c['total'] > 0 ? ' · '.$plata($c['total']) : ''));
            $texto = '*Desposte '.$r['tipo'].' #'.$r['id'].'* — '.\Illuminate\Support\Carbon::parse($r['fecha'])->format('d/m/Y')."\n"
                .'Medias: '.$kg($r['peso_medias']).' · costo '.$plata($r['costo_total'])."\n"
                .'Cortes: '.$kg($r['peso_cortes']).' · rinde '.($r['rendimiento'] !== null ? $pct($r['rendimiento']) : '-')."\n\n"
                .$lineas->implode("\n")
                ."\n\n*Venta: ".$plata($r['valor_total'])."*\nDiferencia: ".$plata($r['diferencia']);
            $tonoDiferencia = $r['diferencia'] >= 0 ? 'border-emerald-200! bg-emerald-50!' : 'border-red-200! bg-red-50!';
        @endphp
        @if ($modoTerminado === 'primario')
            <x-ui.alert class="mb-4 print:hidden">
                Desposte guardado. Las piezas grandes quedaron <strong>disponibles para cuartear</strong>, con el costo repartido por kg.
                <button type="button" wire:click="irACuarteo" class="ml-1 font-semibold underline">Ir a cuarteo</button>
            </x-ui.alert>
        @else
            <x-ui.alert class="mb-4 print:hidden">{{ $modoTerminado === 'cuarteo' ? 'Cuarteo guardado. Las piezas quedaron despostadas.' : 'Desposte guardado. Las medias quedaron despostadas.' }}</x-ui.alert>
        @endif

        <div class="grid gap-3 sm:grid-cols-4">
            <x-ui.stat label="Costo" :value="$plata($r['costo_total'])" />
            <x-ui.stat label="Venta" :value="$plata($r['valor_total'])" />
            <x-ui.stat :label="$r['diferencia'] >= 0 ? 'Ganancia' : 'Pérdida'" :value="$plata($r['diferencia'])" class="{{ $tonoDiferencia }}" />
            <x-ui.stat label="Rinde" :value="$r['rendimiento'] !== null ? $pct($r['rendimiento']) : '—'" :hint="$kg($r['peso_cortes']).' de '.$kg($r['peso_medias'])" />
        </div>

        <x-ui.card title="Cortes" class="mt-4" :padding="false">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr><th class="px-5 py-2.5">Corte</th><th class="px-5 py-2.5 text-right">Kg</th><th class="px-5 py-2.5 text-right">$/kg</th><th class="px-5 py-2.5 text-right">Total</th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($r['cortes'] as $c)
                        <tr>
                            <td class="px-5 py-2 font-medium first-letter:uppercase">{{ $c['nombre'] }}</td>
                            <td class="px-5 py-2 text-right tabular-nums">{{ $kg($c['peso']) }}</td>
                            <td class="px-5 py-2 text-right tabular-nums">{{ $c['precio_kg'] !== null ? $plata($c['precio_kg']) : 'Sin precio' }}</td>
                            <td class="px-5 py-2 text-right font-semibold tabular-nums">{{ $plata($c['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-stone-200 font-bold">
                    <tr><td class="px-5 py-2.5">Total</td><td class="px-5 py-2.5 text-right tabular-nums">{{ $kg($r['peso_cortes']) }}</td><td></td><td class="px-5 py-2.5 text-right tabular-nums">{{ $plata($r['valor_total']) }}</td></tr>
                </tfoot>
            </table>
        </x-ui.card>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end print:hidden">
            <x-ui.button variant="secondary" :href="route('dashboard')">Volver al inicio</x-ui.button>
            <x-ui.button variant="secondary" wire:click="nuevaProduccion" icon="plus">Otra producción</x-ui.button>
            <x-ui.button variant="secondary" icon="printer" onclick="window.print()">Imprimir</x-ui.button>
            <x-ui.button variant="secondary" icon="download" :href="route('produccion.pdf', $r['id'])">Descargar PDF</x-ui.button>
            <a href="https://wa.me/?text={{ rawurlencode($texto) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Enviar por WhatsApp</a>
        </div>
    @endif
</div>
