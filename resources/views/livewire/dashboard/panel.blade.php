{{--
    Dashboard de la carnicería. Una sola vista para las dos variantes:
    "enterprise" (Plan Completo) suma las pestañas Preparados y Cuarteo.
--}}
@php
    $completo = $variant === 'enterprise';
    $tipos = $completo
        ? ['vacuno' => 'Vacuno', 'porcino' => 'Porcino', 'avicola' => 'Avícola', 'preparados' => 'Preparados', 'cuarteo' => 'Cuarteo']
        : ['vacuno' => 'Vacuno', 'porcino' => 'Porcino', 'avicola' => 'Avícola'];
    $emojis = ['vacuno' => '🐄', 'porcino' => '🐖', 'avicola' => '🐔', 'preparados' => '👨‍🍳', 'cuarteo' => '🔪'];
    $rangos = ['dia' => 'Hoy', 'semana' => 'Semana', 'mes' => 'Mes'];
    $ultimos = collect([$vacuno_animals, $porcino_animals, $avicola_animals])
        ->flatten(1)
        ->sortByDesc(fn ($animal) => $animal->fecha->timestamp.str_pad((string) $animal->id, 10, '0', STR_PAD_LEFT))
        ->take(6);
    $emojiAnimal = function (string $nombre): string {
        $n = mb_strtolower($nombre);

        return match (true) {
            str_contains($n, 'porc') || str_contains($n, 'cerdo') => '🐖',
            str_contains($n, 'avi') || str_contains($n, 'pollo') => '🐔',
            default => '🐄',
        };
    };
    // Tarjetas de animales → pantalla de producción de ese tipo (si el plan lo incluye).
    $habilitados = array_map('intval', auth()->user()->carniceria?->tiposAnimalHabilitadosIds() ?? []);
    $tiposPorNombre = \App\Models\AnimalType::query()->pluck('id', 'nombre');
    // Permisos del usuario: el dueño tiene todos; al empleado se le ocultan los accesos que no tiene.
    $puedeIngresos = auth()->user()->puede('ingresos');
    $puedeProducir = auth()->user()->puede('producciones');
    $produccion = fn (string $nombre) => $puedeProducir && isset($tiposPorNombre[$nombre]) && in_array((int) $tiposPorNombre[$nombre], $habilitados, true)
        ? route('produccion', $tiposPorNombre[$nombre])
        : null;
    $campo = 'w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm';
    $etiqueta = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500';
@endphp
<div>
    <x-ui.page-header :title="'Hola, '.(explode(' ', trim(auth()->user()->full_name))[0] ?? '')" subtitle="Resumen de la carnicería y carga rápida de despostes.">
        <x-ui.badge tone="amber" class="px-3 py-1 text-sm">{{ $completo ? 'Plan Completo' : 'Plan '.(auth()->user()->planVigente()?->nombre ?? 'Básico') }}</x-ui.badge>
        @if ($puedeIngresos)
            <x-ui.button :href="route('ingresos.index', ['nuevo' => 1])" variant="accent" icon="plus">Nuevo ingreso</x-ui.button>
        @endif
    </x-ui.page-header>

    {{-- Rango de los indicadores --}}
    <div class="mb-3 flex justify-end">
        <div class="inline-flex rounded-lg border border-stone-200 bg-white p-1 shadow-sm" role="group" aria-label="Rango del resumen">
            @foreach ($rangos as $valor => $texto)
                <button
                    type="button"
                    wire:click="$set('rangoResumen', '{{ $valor }}')"
                    @class([
                        'rounded-md px-3 py-1 text-sm font-medium transition',
                        'bg-stone-900 text-white shadow-sm' => $rangoResumen === $valor,
                        'text-stone-600 hover:text-stone-900' => $rangoResumen !== $valor,
                    ])
                >{{ $texto }}</button>
            @endforeach
        </div>
    </div>

    @php
        // Cuartos / piezas grandes: lleva al cuarteo del animal con más piezas (o a cargar una si no hay).
        $totalPiezas = (int) $piezas_disponibles->sum('cantidad');
        $kgPiezas = (float) $piezas_disponibles->sum('kg');
        $tipoPiezas = $piezas_disponibles->first()?->animal_type_id;
        $hrefPiezas = ! $puedeProducir ? null : ($tipoPiezas
            ? route('produccion', ['tipo' => $tipoPiezas, 'modo' => 'cuarteo'])
            : ($puedeIngresos ? route('ingresos.index', ['nuevo' => 1]) : null));
        $hintPiezas = ! $hrefPiezas ? number_format($kgPiezas, 1, ',', '.').' kg' : ($totalPiezas > 0
            ? number_format($kgPiezas, 1, ',', '.').' kg · Cuartear →'
            : 'Ingresar →');
    @endphp
    {{-- Kg: lo que entró en el rango y lo que queda sin despostar hoy --}}
    <div class="mb-3 grid grid-cols-2 gap-3">
        <x-ui.stat compacto label="Kg ingresados" :value="number_format((float) $total_kg_procesados, 1, ',', '.').' kg'" emoji="⚖️" :hint="['dia' => 'Hoy', 'semana' => 'Esta semana', 'mes' => 'Este mes'][$rangoResumen] ?? null" />
        <x-ui.stat compacto label="Kg restantes" :value="number_format((float) $kg_restantes, 1, ',', '.').' kg'" emoji="📦" hint="Sin despostar (stock actual)" />
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <x-ui.stat compacto label="Reses vacunas" :value="$vacuno_count" emoji="🐄" :href="$produccion('vacuno')" :hint="$produccion('vacuno') ? 'Producir →' : null" />
        <x-ui.stat compacto label="Reses de cerdo" :value="$porcino_count" emoji="🐖" :href="$produccion('porcino')" :hint="$produccion('porcino') ? 'Producir →' : null" />
        <x-ui.stat compacto label="Cajones avícolas" :value="$cajones_count" emoji="🐔" :href="$produccion('aviar')" :hint="$produccion('aviar') ? 'Producir →' : null" />
        <x-ui.stat compacto label="Cuartos y piezas" :value="$totalPiezas" emoji="🔪" :href="$hrefPiezas" :hint="$hintPiezas" />
        {{-- Preparados: solo la tarjeta; el módulo todavía no existe --}}
        <x-ui.stat compacto label="Preparados" value="—" emoji="👨‍🍳" hint="Próximamente" class="opacity-70" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        {{-- Últimos ingresos (compacto) --}}
        <x-ui.card title="Últimos ingresos" class="min-w-0 lg:col-span-2" :padding="false">
            <x-slot:actions>
                @if ($puedeIngresos)
                    <x-ui.button :href="route('ingresos.index')" variant="ghost" size="sm">Ver todos</x-ui.button>
                @endif
            </x-slot:actions>

            @if ($ultimos->isEmpty())
                <div class="p-5"><x-ui.empty emoji="🐄">Todavía no hay ingresos cargados.</x-ui.empty></div>
            @else
                <ul class="divide-y divide-stone-100">
                    @foreach ($ultimos->take(5) as $animal)
                        @php
                            $estado = match ($animal->estado) {
                                \App\Models\Animal::DESPOSTADA => ['Despostada', 'stone'],
                                \App\Models\Animal::EN_DESPOSTE => ['En desposte', 'amber'],
                                default => ['Disponible', 'green'],
                            };
                        @endphp
                        <li>
                            <a @if ($puedeIngresos) href="{{ route('animals.show', $animal) }}" @endif class="flex items-center gap-3 px-5 py-2.5 transition hover:bg-stone-50">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-stone-100">{{ $emojiAnimal($animal->animalType->nombre) }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-stone-900"><span class="capitalize">{{ $animal->animalType->nombre }}</span>{{ $animal->esPieza() ? ' · '.$animal->etiquetaFormato() : '' }} <span class="font-normal text-stone-400">· {{ $animal->fecha->format('d/m') }}</span></p>
                                    <div class="mt-0.5 flex flex-wrap gap-1">
                                        <x-ui.badge :tone="$estado[1]">{{ $estado[0] }}</x-ui.badge>
                                        <x-ui.dias :animal="$animal" />
                                    </div>
                                </div>
                                <p class="text-sm font-semibold tabular-nums text-stone-900">{{ number_format((float) $animal->peso_total, 1, ',', '.') }} kg</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        {{-- Producciones: pendientes y terminadas --}}
        @php
            $tiposPorId = \App\Models\AnimalType::query()->pluck('nombre', 'id');
            $emojiTipo = fn ($id) => ['vacuno' => '🐄', 'porcino' => '🐖', 'aviar' => '🐔'][$tiposPorId[$id] ?? ''] ?? '🔪';
            $kgTexto = fn ($v) => number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 1, ',', '.').' kg';
            // Nombre de las piezas de cada cuarteo pendiente (ej. "Mocho").
            $piezasPendientes = \App\Models\Desposte::query()->with('animales.cutCatalog')
                ->whereIn('id', $producciones_pendientes->where('modo', 'cuarteo')->pluck('id'))->get()
                ->mapWithKeys(fn ($d) => [$d->id => $d->animales->map(fn ($a) => $a->etiquetaFormato())->implode(', ')]);
            $plataTexto = fn ($v) => ((float) $v < 0 ? '-' : '').'$ '.number_format(abs((float) $v), 0, ',', '.');
        @endphp
        <section
            class="min-w-0 rounded-2xl border border-stone-200 bg-white shadow-sm lg:col-span-3"
            x-data="{ vista: @js($producciones_pendientes->isNotEmpty() ? 'pendientes' : 'terminadas') }"
        >
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
                <h2 class="flex items-center gap-2 text-base font-semibold text-stone-900">Producciones @if ($puedeProducir)<a href="{{ route('producciones.index') }}" class="text-xs font-semibold text-amber-700 hover:underline">Ver todas</a>@endif</h2>
                <div class="inline-flex rounded-lg border border-stone-200 bg-stone-50 p-1 text-sm">
                    <button type="button" @click="vista = 'pendientes'" :class="vista === 'pendientes' ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-500 hover:text-stone-900'" class="inline-flex items-center gap-1.5 rounded-md px-3 py-1 font-medium transition">
                        Pendientes
                        @if ($producciones_pendientes->isNotEmpty())
                            <span class="rounded-full bg-amber-500 px-1.5 text-xs font-bold text-stone-950">{{ $producciones_pendientes->count() }}</span>
                        @endif
                    </button>
                    <button type="button" @click="vista = 'terminadas'" :class="vista === 'terminadas' ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-500 hover:text-stone-900'" class="rounded-md px-3 py-1 font-medium transition">Terminadas</button>
                </div>
            </header>

            {{-- Pendientes --}}
            <div x-show="vista === 'pendientes'">
                @if ($producciones_pendientes->isEmpty())
                    <div class="p-5"><x-ui.empty emoji="✅">No hay despostes pendientes.</x-ui.empty></div>
                @else
                    <ul class="divide-y divide-stone-100">
                        @foreach ($producciones_pendientes as $p)
                            <li class="flex items-center gap-3 px-5 py-3">
                                <span class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100">
                                    {{ $emojiTipo($p['animal_type_id']) }}
                                    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white bg-amber-500"></span>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-stone-900">
                                        {{ $p['tipo'] }} · {{ $p['modo'] === 'cuarteo' ? ($piezasPendientes[$p['id']] ?? 'pieza') : $p['medias'].' '.($p['medias'] === 1 ? 'unidad' : 'unidades') }} · {{ $kgTexto($p['peso_medias']) }}
                                        @if ($p['modo'] !== 'musculo')<x-ui.badge tone="amber" class="ml-1">{{ \App\Models\Desposte::MODOS[$p['modo']] }}</x-ui.badge>@endif
                                    </p>
                                    <p class="truncate text-xs text-stone-500">Cargado {{ $kgTexto($p['peso_cortes']) }} · desde {{ \Illuminate\Support\Carbon::parse($p['fecha'])->format('d/m') }}{{ $p['despostador'] ? ' · '.$p['despostador'] : '' }}</p>
                                </div>
                                @if ($puedeProducir)
                                    <x-ui.button size="sm" variant="accent" :href="route('produccion', ['tipo' => $p['animal_type_id'], 'desposte' => $p['id']])">Seguir</x-ui.button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Terminadas --}}
            <div x-show="vista === 'terminadas'" x-cloak>
                @if ($producciones_terminadas->isEmpty())
                    <div class="p-5"><x-ui.empty emoji="🔪">Todavía no hay despostes terminados. Tocá un animal arriba para producir.</x-ui.empty></div>
                @else
                    <ul class="divide-y divide-stone-100">
                        @foreach ($producciones_terminadas as $p)
                            <li class="flex items-center gap-3 px-5 py-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-stone-100">{{ $emojiTipo($p['animal_type_id']) }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-stone-900">{{ $p['tipo'] }} · {{ $p['medias'] }} {{ $p['medias'] === 1 ? 'unidad' : 'unidades' }} <span class="font-normal text-stone-400">· {{ \Illuminate\Support\Carbon::parse($p['fecha'])->format('d/m') }}</span></p>
                                    <p class="text-xs text-stone-500">{{ $kgTexto($p['peso_cortes']) }} de {{ $kgTexto($p['peso_medias']) }} · rinde {{ $p['rendimiento'] !== null ? number_format($p['rendimiento'], 1, ',', '.').'%' : '—' }}</p>
                                </div>
                                <div class="text-right">
                                    <p @class(['text-sm font-bold tabular-nums', 'text-emerald-700' => $p['diferencia'] >= 0, 'text-red-700' => $p['diferencia'] < 0])>{{ $plataTexto($p['diferencia']) }}</p>
                                    <p class="text-xs text-stone-400">{{ $p['diferencia'] >= 0 ? 'ganancia' : 'pérdida' }}</p>
                                </div>
                                @if ($puedeProducir)
                                <a href="{{ route('produccion.pdf', $p['id']) }}" class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-stone-700" title="Descargar PDF" aria-label="Descargar PDF del desposte {{ $p['id'] }}">
                                    <x-ui.icon name="download" class="h-4 w-4" />
                                </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>

    @if ($puedeProducir)
    {{-- Carga rápida por tipo --}}
    <section
        class="mt-6 rounded-2xl border border-stone-200 bg-white shadow-sm"
        x-data="{
            activeTab: 'vacuno',
            kinds: @js(array_keys($tipos)),
            titles: @js($tipos),
            emojis: @js($emojis),
            storageKey: @js('carnicos.'.$variant.'.pendingDespostes'),
            cutCatalog: @js($cut_catalog_by_kind ?? []),
            formByKind: {
                vacuno:      { cantidad: 1, pesoTotal: 0, frigorifico: '', precioCompra: 0 },
                porcino:     { cantidad: 1, pesoTotal: 0, frigorifico: '', precioCompra: 0 },
                avicola:     { cantidad: 1, pesoTotal: 0, frigorifico: '', precioCompra: 0 },
                preparados:  { cantidad: 1, pesoTotal: 0, frigorifico: '', precioCompra: 0 },
                cuarteo:     { cantidad: 1, pesoTotal: 0, frigorifico: '', precioCompra: 0, subtipo: '' },
            },
            rowsByKind: {},
            cutsViewTab: 'activos',
            showGuardarModal: false,
            guardarImprimir: false,
            whatsappTel: '',
            pendingDespostes: [],
            margenPct: 30,
            margenTipo: 'markup',
            init() {
                this.kinds.forEach((kind) => {
                    const catalog = this.cutCatalog[kind] || [];
                    this.rowsByKind[kind] = catalog.map((item) => ({
                        id: item.id,
                        nombre: item.nombre,
                        cantidad: Number(item.cantidad_esperada || 1),
                        peso: 0,
                        precio: 0,
                        activo: Boolean(item.activo),
                        ultimoPrecio: item.ultimo_precio ? Number(item.ultimo_precio) : null,
                    }));
                });

                try {
                    const raw = localStorage.getItem(this.storageKey);
                    this.pendingDespostes = raw ? JSON.parse(raw) : [];
                } catch (_) {
                    this.pendingDespostes = [];
                }
            },
            tabTitle(kind) {
                return this.titles[kind] || kind;
            },
            activeRows() {
                return this.rowsByKind[this.activeTab] || [];
            },
            visibleRows() {
                const rows = this.activeRows();
                if (this.cutsViewTab === 'desactivados') {
                    return rows.filter((row) => !row.activo);
                }
                return rows.filter((row) => row.activo);
            },
            activeRowsCount() {
                return this.activeRows().filter((row) => row.activo).length;
            },
            inactiveRowsCount() {
                return this.activeRows().filter((row) => !row.activo).length;
            },
            openGuardarModal(imprimir = false) {
                this.guardarImprimir = imprimir;
                this.showGuardarModal = true;
            },
            syncPendientes() {
                try {
                    localStorage.setItem(this.storageKey, JSON.stringify(this.pendingDespostes));
                } catch (_) {
                    // Sin almacenamiento disponible, seguimos sin romper el flujo.
                }
            },
            closeGuardarModal() {
                this.showGuardarModal = false;
            },
            alertasGuardado() {
                const alerts = [];
                if (this.alertaRendimientoBajo()) {
                    alerts.push('Rendimiento en kg por debajo del 70%.');
                }
                if (this.alertaRentabilidadBaja()) {
                    alerts.push(this.textoAlertaRentabilidad());
                }
                return alerts;
            },
            confirmarGuardado() {
                const alerts = this.alertasGuardado();
                if (alerts.length > 0) {
                    const detalle = alerts.map((line) => `- ${line}`).join('\n');
                    const ok = window.confirm(`Hay advertencias para este desposte:\n\n${detalle}\n\n¿Querés guardar de todos modos?`);
                    if (!ok) return;
                }

                this.showGuardarModal = false;
                if (this.guardarImprimir) {
                    window.print();
                }
            },
            dejarPendiente() {
                const kind = this.activeTab;
                const data = this.formByKind[kind] || {};

                this.pendingDespostes.unshift({
                    id: Date.now(),
                    fecha: new Date().toISOString(),
                    tipo: kind,
                    subtipo: data.subtipo || '',
                    frigorifico: data.frigorifico || '',
                    pesoTotal: Number(data.pesoTotal) || 0,
                    rendimientoKg: this.rendimientoPct(),
                    rendimientoEconomico: this.rendimientoEconomico(),
                });

                if (this.pendingDespostes.length > 50) {
                    this.pendingDespostes = this.pendingDespostes.slice(0, 50);
                }

                this.syncPendientes();
                this.showGuardarModal = false;
                alert('Desposte marcado como pendiente.');
            },
            resumenTexto() {
                const kind = this.activeTab;
                const data = this.formByKind[kind] || {};
                const label = this.tabTitle(kind);
                const rows = (this.rowsByKind[kind] || []).filter(r => r.activo);
                let txt = `*Resumen desposte - ${label}*\n`;
                if (data.frigorifico) txt += `Frigorífico: ${data.frigorifico}\n`;
                if (kind === 'cuarteo' && data.subtipo) txt += `Subtipo de cuarteo: ${data.subtipo}\n`;
                if (kind === 'avicola') {
                    txt += `Cantidad de cajones: ${this.formatKg(data.pesoTotal || 0)}\n`;
                    txt += `Precio compra/cajón: $${this.formatMoney(data.precioCompra || 0)}\n`;
                } else {
                    txt += `Peso total: ${this.formatKg(data.pesoTotal || 0)} kg\n`;
                    txt += `Precio compra/kg: $${this.formatMoney(data.precioCompra || 0)}\n`;
                }
                txt += `Total compra: $${this.formatMoney(this.totalCompra())}\n`;
                txt += `\n*Cortes:*\n`;
                rows.forEach(r => {
                    if ((Number(r.peso) || 0) > 0) {
                        txt += `• ${r.nombre}: ${this.formatKg(r.peso)} kg`;
                        if ((Number(r.precio) || 0) > 0) txt += ` · $${this.formatMoney(this.rowSubtotal(r))}`;
                        txt += `\n`;
                    }
                });
                txt += `\n*Total cortes: $${this.formatMoney(this.totalValorCortes())}*\n`;
                txt += `Rendimiento kg: ${this.formatPct(this.rendimientoPct())}%\n`;
                txt += `Rendimiento económico: ${this.formatPct(this.rendimientoEconomico())}%`;
                return txt;
            },
            enviarWhatsApp(telefono) {
                const txt = encodeURIComponent(this.resumenTexto());
                const url = telefono
                    ? `https://wa.me/${telefono.replace(/\D/g,'')}?text=${txt}`
                    : `https://wa.me/?text=${txt}`;
                window.open(url, '_blank');
            },
            rowSubtotal(row) {
                return (Number(row.peso) || 0) * (Number(row.precio) || 0);
            },
            totalPesoCortes(kind = null) {
                const key = kind || this.activeTab;
                return (this.rowsByKind[key] || [])
                    .filter((row) => row.activo)
                    .reduce((sum, row) => sum + (Number(row.peso) || 0), 0);
            },
            totalValorCortes(kind = null) {
                const key = kind || this.activeTab;
                return (this.rowsByKind[key] || [])
                    .filter((row) => row.activo)
                    .reduce((sum, row) => sum + this.rowSubtotal(row), 0);
            },
            totalCompra(kind = null) {
                const key = kind || this.activeTab;
                const data = this.formByKind[key] || {};
                return (Number(data.pesoTotal) || 0) * (Number(data.precioCompra) || 0);
            },
            cantidadBaseKg(kind = null) {
                const key = kind || this.activeTab;
                const data = this.formByKind[key] || {};
                const valor = Number(data.pesoTotal) || 0;
                return key === 'avicola' ? valor * 20 : valor;
            },
            diferenciaKg(kind = null) {
                const key = kind || this.activeTab;
                return this.totalPesoCortes(key) - this.cantidadBaseKg(key);
            },
            diferenciaValor(kind = null) {
                return this.totalValorCortes(kind) - this.totalCompra(kind);
            },
            rendimiento(kind = null) {
                const key = kind || this.activeTab;
                const pesoBase = this.cantidadBaseKg(key);
                if (!pesoBase) return 0;
                return this.totalPesoCortes(key) / pesoBase;
            },
            rendimientoPct(kind = null) {
                return this.rendimiento(kind) * 100;
            },
            rendimientoEconomico(kind = null) {
                const compra = this.totalCompra(kind);
                if (!compra) return 0;
                return ((this.totalValorCortes(kind) - compra) / compra) * 100;
            },
            margenObjetivo() {
                const pct = Number(this.margenPct) || 0;
                // Mark Up: ganancia / costo. Margen: ganancia / venta.
                // Convertimos a rendimiento económico % (= mark up) para comparar uniformemente.
                if (this.margenTipo === 'margen') {
                    // margen% = markup / (1 + markup) → markup = margen / (1 - margen)
                    if (pct >= 100) return Infinity;
                    return (pct / (100 - pct)) * 100;
                }
                return pct;
            },
            margenIndicador() {
                const rend = this.rendimientoEconomico();
                const obj  = this.margenObjetivo();
                if (!obj) return null;
                const diff = rend - obj;
                if (Math.abs(diff) < 0.5) return { label: 'Igual al objetivo', clase: 'bg-stone-100 text-stone-700', icon: '=' };
                if (diff > 0) return { label: '+' + diff.toFixed(1) + '% sobre el objetivo', clase: 'bg-emerald-50 text-emerald-700', icon: '▲' };
                return { label: diff.toFixed(1) + '% bajo el objetivo', clase: 'bg-red-50 text-red-700', icon: '▼' };
            },
            alertaRendimientoBajo() {
                const pesoBase = this.cantidadBaseKg();
                if (!pesoBase) return false;
                return this.rendimiento() < 0.7;
            },
            alertaRentabilidadBaja() {
                const compra = this.totalCompra();
                if (!compra) return false;
                const rendEco = this.rendimientoEconomico();
                const objetivo = this.margenObjetivo();

                // Si no hay objetivo configurado, alertamos solo cuando hay pérdida.
                if (!objetivo || !Number.isFinite(objetivo)) {
                    return rendEco < 0;
                }

                return rendEco < objetivo;
            },
            textoAlertaRentabilidad() {
                const rendEco = this.rendimientoEconomico();
                const objetivo = this.margenObjetivo();

                if (!objetivo || !Number.isFinite(objetivo)) {
                    return 'La rentabilidad económica está en pérdida. Revisá precios de compra y venta.';
                }

                const diff = (objetivo - rendEco).toFixed(1);
                return `La rentabilidad está ${diff}% por debajo del objetivo. Revisá precios de cortes y compra.`;
            },
            formatMoney(value) {
                return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0));
            },
            formatKg(value) {
                return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(Number(value || 0));
            },
            formatPct(value) {
                return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0));
            },
        }"
    >
        <header class="flex flex-col gap-4 border-b border-stone-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-base font-semibold text-stone-900">Carga rápida de desposte</h2>
                <p class="mt-0.5 text-sm text-stone-500">Ingreso y cortes en una sola vista, con el rendimiento al instante.</p>
            </div>
            <div class="flex flex-wrap gap-1.5" role="tablist">
                @foreach ($tipos as $clave => $texto)
                    <button
                        type="button"
                        role="tab"
                        @click="activeTab = '{{ $clave }}'"
                        :class="activeTab === '{{ $clave }}' ? 'border-amber-500 bg-amber-50 text-amber-900 ring-1 ring-amber-500' : 'border-stone-200 bg-white text-stone-600 hover:border-stone-300 hover:text-stone-900'"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition"
                    ><span>{{ $emojis[$clave] }}</span>{{ $texto }}</button>
                @endforeach
            </div>
        </header>

        <div class="space-y-5 p-5">
            {{-- Datos del ingreso --}}
            <div class="grid gap-3 rounded-xl bg-stone-50 p-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="{{ $etiqueta }}">Frigorífico</label>
                    <input x-model="formByKind[activeTab].frigorifico" type="text" maxlength="150" class="{{ $campo }}" placeholder="Nombre del frigorífico">
                </div>
                <div x-show="activeTab === 'cuarteo'" x-cloak>
                    <label class="{{ $etiqueta }}">Subtipo de cuarteo</label>
                    <input x-model="formByKind.cuarteo.subtipo" type="text" maxlength="120" class="{{ $campo }}" placeholder="Delantero, trasero, 4 cuartos…">
                </div>
                <div>
                    <label class="{{ $etiqueta }}" x-text="activeTab === 'avicola' ? 'Cantidad de cajones' : 'Peso total (kg)'"></label>
                    <input x-model.number="formByKind[activeTab].pesoTotal" type="number" min="0" step="0.001" class="{{ $campo }}" :placeholder="activeTab === 'avicola' ? '0' : '0.000'">
                </div>
                <div x-show="activeTab === 'avicola'" x-cloak>
                    <label class="{{ $etiqueta }}">Total kg</label>
                    <input type="text" :value="formatKg((Number(formByKind.avicola.pesoTotal) || 0) * 20) + ' kg'" readonly class="{{ $campo }} font-semibold">
                </div>
                <div>
                    <label class="{{ $etiqueta }}" x-text="activeTab === 'avicola' ? 'Precio compra por cajón' : 'Precio compra por kg'"></label>
                    <input x-model.number="formByKind[activeTab].precioCompra" type="number" min="0" step="0.01" class="{{ $campo }}" placeholder="0,00">
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Total compra</label>
                    <input type="text" :value="'$ ' + formatMoney(totalCompra())" readonly class="{{ $campo }} font-semibold">
                </div>
            </div>

            {{-- Cortes --}}
            <div>
                <div class="mb-2 inline-flex rounded-lg border border-stone-200 bg-stone-50 p-1 text-sm">
                    <button type="button" @click="cutsViewTab = 'activos'" :class="cutsViewTab === 'activos' ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-500'" class="rounded-md px-3 py-1 font-medium transition">
                        Activos (<span x-text="activeRowsCount()"></span>)
                    </button>
                    <button type="button" @click="cutsViewTab = 'desactivados'" :class="cutsViewTab === 'desactivados' ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-500'" class="rounded-md px-3 py-1 font-medium transition">
                        Desactivados (<span x-text="inactiveRowsCount()"></span>)
                    </button>
                </div>

                <div class="overflow-x-auto rounded-xl border border-stone-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                            <tr>
                                <th class="px-3 py-2.5">Corte</th>
                                <th class="px-3 py-2.5">Peso (kg)</th>
                                <th class="px-3 py-2.5" x-text="activeTab === 'avicola' ? 'Precio por unidad' : 'Precio por kg'"></th>
                                <th class="px-3 py-2.5 text-right">Subtotal</th>
                                <th class="px-3 py-2.5 text-right">Últ. precio</th>
                                <th class="px-3 py-2.5 text-right">% kg</th>
                                <th class="px-3 py-2.5 text-right">% $</th>
                                <th class="px-3 py-2.5">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <template x-for="row in visibleRows()" :key="`${activeTab}-${row.id}`">
                                <tr :class="row.activo ? 'hover:bg-amber-50/40' : 'bg-stone-50 text-stone-400'">
                                    <td class="px-3 py-2">
                                        <span class="font-medium" :class="row.activo ? 'text-stone-900' : 'text-stone-500'" x-text="row.nombre"></span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input x-model.number="row.peso" type="number" min="0" step="0.001" :disabled="!row.activo" class="w-28 rounded-md border border-stone-300 px-2 py-1.5 text-right text-sm tabular-nums">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input x-model.number="row.precio" type="number" min="0" step="0.01" :disabled="!row.activo" class="w-28 rounded-md border border-stone-300 px-2 py-1.5 text-right text-sm tabular-nums">
                                    </td>
                                    <td class="px-3 py-2 text-right font-semibold tabular-nums" :class="row.activo ? 'text-stone-900' : 'text-stone-500'">
                                        $ <span x-text="formatMoney(row.activo ? rowSubtotal(row) : 0)"></span>
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums">
                                        <span
                                            x-show="row.ultimoPrecio !== null"
                                            class="text-xs font-medium"
                                            :class="row.precio > 0 && row.precio > row.ultimoPrecio ? 'text-red-600' : (row.precio > 0 && row.precio < row.ultimoPrecio ? 'text-emerald-600' : 'text-stone-500')"
                                            x-text="'$ ' + formatMoney(row.ultimoPrecio)"
                                        ></span>
                                        <span x-show="row.ultimoPrecio === null" class="text-xs text-stone-300">—</span>
                                    </td>
                                    <td class="px-3 py-2 text-right text-xs font-semibold tabular-nums text-stone-700">
                                        <span x-text="cantidadBaseKg() > 0 && row.activo && (Number(row.peso) || 0) > 0 ? ((Number(row.peso) / cantidadBaseKg()) * 100).toFixed(1) + '%' : '—'"></span>
                                    </td>
                                    <td class="px-3 py-2 text-right text-xs font-semibold tabular-nums text-stone-700">
                                        <span x-text="totalCompra() > 0 && row.activo && rowSubtotal(row) > 0 ? ((rowSubtotal(row) / totalCompra()) * 100).toFixed(1) + '%' : '—'"></span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="row.activo ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-200 text-stone-600'" x-text="row.activo ? 'Activo' : 'Inactivo'"></span>
                                            <button
                                                type="button"
                                                x-show="row.activo"
                                                @click="if (confirm('Se dará de baja este corte del catálogo. ¿Continuar?')) { $wire.darBajaCorteCatalogo(row.id) }"
                                                class="rounded-md px-2 py-1 text-xs font-medium text-stone-500 hover:bg-stone-100 hover:text-red-700"
                                            >Dar de baja</button>
                                            <button
                                                type="button"
                                                x-show="!row.activo"
                                                @click="$wire.darAltaCorteCatalogo(row.id)"
                                                class="rounded-md px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50"
                                            >Dar de alta</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="visibleRows().length === 0">
                                <td colspan="8" class="px-3 py-8 text-center text-sm text-stone-500">No hay cortes en esta categoría.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Totales --}}
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7">
                <div class="rounded-xl border border-stone-200 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Kg cortes</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-stone-900" x-text="formatKg(totalPesoCortes())"></p>
                </div>
                <div class="rounded-xl border px-4 py-3" :class="alertaRendimientoBajo() ? 'border-red-200 bg-red-50' : 'border-stone-200'">
                    <p class="text-xs font-medium uppercase tracking-wide" :class="alertaRendimientoBajo() ? 'text-red-600' : 'text-stone-500'">Rinde kg</p>
                    <p class="mt-1 text-lg font-bold tabular-nums" :class="alertaRendimientoBajo() ? 'text-red-700' : 'text-stone-900'" x-text="formatPct(rendimientoPct()) + '%'"></p>
                </div>
                <div class="rounded-xl border border-stone-200 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Rinde $</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-stone-900" x-text="formatPct(rendimientoEconomico()) + '%'"></p>
                </div>
                <div class="rounded-xl border border-stone-200 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Compra</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-stone-900" x-text="'$ ' + formatMoney(totalCompra())"></p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-amber-800">Venta cortes</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-amber-950" x-text="'$ ' + formatMoney(totalValorCortes())"></p>
                </div>
                <div class="rounded-xl border px-4 py-3" :class="diferenciaKg() >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'">
                    <p class="text-xs font-medium uppercase tracking-wide" :class="diferenciaKg() >= 0 ? 'text-emerald-700' : 'text-red-700'">Diferencia kg</p>
                    <p class="mt-1 text-lg font-bold tabular-nums" :class="diferenciaKg() >= 0 ? 'text-emerald-900' : 'text-red-900'" x-text="(diferenciaKg() >= 0 ? '+' : '') + formatKg(diferenciaKg())"></p>
                </div>
                <div class="rounded-xl border px-4 py-3" :class="diferenciaValor() >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'">
                    <p class="text-xs font-medium uppercase tracking-wide" :class="diferenciaValor() >= 0 ? 'text-emerald-700' : 'text-red-700'">Diferencia $</p>
                    <p class="mt-1 text-lg font-bold tabular-nums" :class="diferenciaValor() >= 0 ? 'text-emerald-900' : 'text-red-900'" x-text="(diferenciaValor() >= 0 ? '+ $ ' : '- $ ') + formatMoney(Math.abs(diferenciaValor()))"></p>
                </div>
            </div>

            {{-- Margen objetivo --}}
            <div class="flex flex-wrap items-center gap-3 rounded-xl bg-stone-50 px-4 py-3">
                <span class="text-xs font-semibold uppercase tracking-wide text-stone-500">Objetivo</span>
                <select x-model="margenTipo" class="rounded-lg border border-stone-300 px-2 py-1.5 text-sm">
                    <option value="margen">Margen</option>
                    <option value="markup">Mark Up</option>
                </select>
                <div class="flex items-center overflow-hidden rounded-lg border border-stone-300 bg-white">
                    <input x-model.number="margenPct" type="number" min="0" max="999" step="0.5" class="w-20 border-0 px-3 py-1.5 text-sm focus-visible:ring-0">
                    <span class="border-l border-stone-300 bg-stone-100 px-2 py-1.5 text-sm font-semibold text-stone-600">%</span>
                </div>
                <template x-if="margenIndicador()">
                    <span class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold" :class="margenIndicador().clase">
                        <span x-text="margenIndicador().icon"></span>
                        <span x-text="margenIndicador().label"></span>
                    </span>
                </template>
            </div>

            {{-- Avisos --}}
            <div x-show="alertaRendimientoBajo()" x-cloak>
                <x-ui.alert tone="red">El rendimiento en kg está por debajo del 70%.</x-ui.alert>
            </div>
            <div x-show="alertaRentabilidadBaja()" x-cloak>
                <x-ui.alert tone="amber"><span x-text="textoAlertaRentabilidad()"></span></x-ui.alert>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-stone-100 pt-4">
                <x-ui.button variant="ghost" icon="clock" @click="dejarPendiente()">Dejar pendiente</x-ui.button>
                <x-ui.button variant="secondary" icon="printer" @click="openGuardarModal(true)">Guardar e imprimir</x-ui.button>
                <x-ui.button variant="primary" icon="check" @click="openGuardarModal(false)">Guardar desposte</x-ui.button>
            </div>
        </div>

        {{-- Modal de resumen --}}
        <div
            x-show="showGuardarModal"
            x-cloak
            x-transition.opacity
            @keydown.escape.window="closeGuardarModal()"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center"
        >
            <div class="absolute inset-0 bg-stone-950/60" @click="closeGuardarModal()"></div>
            <div class="relative z-10 my-4 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl sm:my-0">
                <div class="flex items-start justify-between gap-3 border-b border-stone-100 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-bold text-stone-900" x-text="guardarImprimir ? 'Guardar e imprimir' : 'Guardar desposte'"></h3>
                        <p class="mt-0.5 text-sm text-stone-500">Resumen del desposte de <span class="font-semibold text-stone-700" x-text="tabTitle(activeTab)"></span></p>
                    </div>
                    <button type="button" @click="closeGuardarModal()" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 hover:text-stone-700" aria-label="Cerrar">
                        <x-ui.icon name="x" />
                    </button>
                </div>

                <div class="space-y-4 p-5">
                    <pre class="whitespace-pre-wrap rounded-xl bg-stone-50 p-4 font-mono text-xs leading-relaxed text-stone-700" x-text="resumenTexto()"></pre>

                    <div>
                        <label class="{{ $etiqueta }}">WhatsApp (opcional)</label>
                        <input x-model="whatsappTel" type="tel" class="{{ $campo }}" placeholder="5491112345678 (con código de país, sin +)">
                        <p class="mt-1 text-xs text-stone-500">Dejalo vacío para elegir el contacto en WhatsApp.</p>
                    </div>

                    <div x-show="alertasGuardado().length" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-800">Advertencias</p>
                        <ul class="mt-1 list-disc space-y-1 pl-4 text-xs font-medium text-amber-900">
                            <template x-for="(item, idx) in alertasGuardado()" :key="idx">
                                <li x-text="item"></li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-2 border-t border-stone-100 px-5 py-4">
                    <button type="button" @click="enviarWhatsApp(whatsappTel)" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        WhatsApp
                    </button>
                    <x-ui.button variant="secondary" @click="closeGuardarModal()">Cancelar</x-ui.button>
                    <x-ui.button variant="primary" @click="confirmarGuardado()"><span x-text="guardarImprimir ? 'Guardar e imprimir' : 'Guardar desposte'"></span></x-ui.button>
                </div>
            </div>
        </div>
    </section>
    @endif
</div>
