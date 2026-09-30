<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-600">Vista compacta para operación diaria con foco en carga rápida y actividad reciente.</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2.5 rounded-2xl bg-gradient-to-r from-sky-100 via-cyan-50 to-blue-100 px-6 py-3 shadow-lg ring-2 ring-sky-300/60">
                <svg class="h-6 w-6 text-sky-600 drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                <div class="flex flex-col leading-none">
                    <span class="text-lg font-black uppercase tracking-widest text-sky-800 drop-shadow-sm">Enterprise</span>
                    <span class="tg-widest text-sky-600">Service</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">Resumen operativo</p>
                <div class="flex items-center gap-2">
                    <label for="rango-resumen" class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Rango</label>
                    <select id="rango-resumen" wire:model.change="rangoResumen" class="rounded-lg border border-indigo-300 bg-white px-3 py-1.5 text-sm font-medium text-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="dia">Día</option>
                        <option value="semana">Semana</option>
                        <option value="mes">Mes</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-4">
                <div class="min-w-24 flex-1">
                    <p class="text-xs text-indigo-600">Reses vacunas</p>
                    <p class="mt-0.5 text-2xl font-bold text-indigo-900">{{ $vacuno_count }}</p>
                </div>
                <div class="min-w-24 flex-1">
                    <p class="text-xs text-indigo-600">Reses de cerdo</p>
                    <p class="mt-0.5 text-2xl font-bold text-indigo-900">{{ $porcino_count }}</p>
                </div>
                <div class="min-w-24 flex-1">
                    <p class="text-xs text-indigo-600">Cajones avícolas</p>
                    <p class="mt-0.5 text-2xl font-bold text-indigo-900">{{ $cajones_count }}</p>
                </div>
                <div class="min-w-24 flex-1">
                    <p class="text-xs text-indigo-600">Total kg procesados</p>
                    <p class="mt-0.5 text-2xl font-bold text-indigo-900">{{ number_format((float) $total_kg_procesados, 1) }}</p>
                </div>
            </div>

            <div class="mt-5 border-t border-indigo-200 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Actividad reciente</p>
                <div class="mt-3 space-y-2">
                    @php
                        $ultimos = collect([$vacuno_animals, $porcino_animals, $avicola_animals])
                            ->flatten(1)
                            ->sortByDesc(fn ($animal) => $animal->fecha->timestamp . str_pad((string) $animal->id, 10, '0', STR_PAD_LEFT))
                            ->take(6);
                    @endphp

                    @forelse($ultimos as $animal)
                        <a href="{{ route('animals.show', $animal) }}" class="flex items-center justify-between rounded-xl border border-indigo-200 bg-white px-4 py-2.5 hover:bg-indigo-100">
                            <div>
                                <p class="font-medium capitalize text-slate-900">{{ $animal->animalType->nombre }}</p>
                                <p class="text-xs text-slate-500">{{ $animal->fecha->format('Y-m-d') }} · {{ number_format((float) $animal->peso_total, 3) }} {{ str_contains(strtolower($animal->animalType->nombre), 'avi') || str_contains(strtolower($animal->animalType->nombre), 'pollo') ? 'cant.' : 'kg' }}</p>
                            </div>
                            <span class="text-sm font-semibold text-slate-700">{{ $animal->cuts_count }} cortes</span>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-indigo-300 px-4 py-5 text-center text-sm text-indigo-700">
                            Todavía no hay ingresos cargados.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <section
        class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        x-data="{
            activeTab: 'vacuno',
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
                const kinds = ['vacuno', 'porcino', 'avicola', 'preparados', 'cuarteo'];
                kinds.forEach((kind) => {
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
                    const raw = localStorage.getItem('carnicos.enterprise.pendingDespostes');
                    this.pendingDespostes = raw ? JSON.parse(raw) : [];
                } catch (_) {
                    this.pendingDespostes = [];
                }
            },
            tabTitle(kind) {
                if (kind === 'vacuno')     return 'Vacuno';
                if (kind === 'porcino')    return 'Porcino';
                if (kind === 'avicola')    return 'Avícola';
                if (kind === 'preparados') return 'Preparados';
                return 'Cuarteo';
            },
            tabBadgeClass(kind) {
                if (kind === 'vacuno') return 'bg-red-100 text-red-800 ring-red-200';
                if (kind === 'porcino') return 'bg-pink-100 text-pink-800 ring-pink-200';
                if (kind === 'avicola') return 'bg-yellow-100 text-yellow-800 ring-yellow-200';
                if (kind === 'preparados') return 'bg-emerald-100 text-emerald-800 ring-emerald-200';
                return 'bg-violet-100 text-violet-800 ring-violet-200';
            },
            tabBadgeStyle(kind) {
                if (kind === 'vacuno') return { backgroundColor: '#fee2e2', color: '#991b1b', borderColor: '#fecaca' };
                if (kind === 'porcino') return { backgroundColor: '#fce7f3', color: '#9d174d', borderColor: '#fbcfe8' };
                if (kind === 'avicola') return { backgroundColor: '#fef9c3', color: '#854d0e', borderColor: '#fde68a' };
                if (kind === 'preparados') return { backgroundColor: '#d1fae5', color: '#065f46', borderColor: '#a7f3d0' };
                return { backgroundColor: '#ede9fe', color: '#5b21b6', borderColor: '#ddd6fe' };
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
                    localStorage.setItem('carnicos.enterprise.pendingDespostes', JSON.stringify(this.pendingDespostes));
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
                txt += `Rendimiento kg: ${new Intl.NumberFormat('es-AR',{minimumFractionDigits:2,maximumFractionDigits:2}).format(this.rendimientoPct())}%\n`;
                txt += `Rendimiento económico: ${new Intl.NumberFormat('es-AR',{minimumFractionDigits:2,maximumFractionDigits:2}).format(this.rendimientoEconomico())}%`;
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
                const pesoBase = this.cantidadBaseKg(key);
                return this.totalPesoCortes(key) - pesoBase;
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
                return pct; // mark up ya es sobre costo
            },
            margenIndicador() {
                const rend = this.rendimientoEconomico();
                const obj  = this.margenObjetivo();
                if (!obj) return null;
                const diff = rend - obj;
                if (Math.abs(diff) < 0.5) return { label: 'Igual al objetivo', color: 'text-slate-600', icon: '=' };
                if (diff > 0) return { label: '+' + diff.toFixed(1) + '% sobre objetivo', color: 'text-emerald-700', icon: '▲' };
                return { label: diff.toFixed(1) + '% bajo objetivo', color: 'text-red-600', icon: '▼' };
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
        }"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-slate-900">Carga rápida por tipo</h2>
                    <span x-text="tabTitle(activeTab)" :style="tabBadgeStyle(activeTab)" class="rounded-full border px-3 py-1 text-sm font-semibold"></span>
                </div>
                <p class="mt-1 text-sm text-slate-600">Completá ingreso y cortes en una sola vista con rendimiento estimado.</p>
            </div>
            <div class="inline-flex flex-wrap rounded-xl border border-slate-200 bg-slate-50 p-1 text-sm gap-0.5">
                <button type="button" @click="activeTab = 'vacuno'"     :class="activeTab === 'vacuno'     ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'" class="rounded-lg px-3 py-1.5 font-medium transition">Vacuno</button>
                <button type="button" @click="activeTab = 'porcino'"    :class="activeTab === 'porcino'    ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'" class="rounded-lg px-3 py-1.5 font-medium transition">Porcino</button>
                <button type="button" @click="activeTab = 'avicola'"    :class="activeTab === 'avicola'    ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'" class="rounded-lg px-3 py-1.5 font-medium transition">Avícola</button>
                <button type="button" @click="activeTab = 'preparados'" :class="activeTab === 'preparados' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'" class="rounded-lg px-3 py-1.5 font-medium transition">Preparados</button>
                <button type="button" @click="activeTab = 'cuarteo'"    :class="activeTab === 'cuarteo'    ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'" class="rounded-lg px-3 py-1.5 font-medium transition">Cuarteo</button>
            </div>
        </div>

        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-wrap gap-3">
                <div class="min-w-0 basis-full sm:basis-[calc(50%-0.375rem)] lg:basis-[calc(33.333%-0.5rem)] flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Frigorífico</label>
                    <input x-model="formByKind[activeTab].frigorifico" type="text" maxlength="150" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Nombre del frigorífico">
                </div>
                <div x-show="activeTab === 'cuarteo'" class="min-w-0 basis-full sm:basis-[calc(50%-0.375rem)] lg:basis-[calc(33.333%-0.5rem)] flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Subtipo de cuarteo</label>
                    <input x-model="formByKind.cuarteo.subtipo" type="text" maxlength="120" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Ej: delantero, trasero, media res, 4 cuartos">
                </div>
                <div class="min-w-0 basis-full sm:basis-[calc(50%-0.375rem)] lg:basis-[calc(33.333%-0.5rem)] flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" x-text="activeTab === 'avicola' ? 'Cantidad de cajones' : 'Peso total (kg)'"></label>
                    <input x-model.number="formByKind[activeTab].pesoTotal" type="number" min="0" step="0.001" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" :placeholder="activeTab === 'avicola' ? '0' : '0.000'">
                </div>
                <div x-show="activeTab === 'avicola'" class="min-w-0 basis-full sm:basis-[calc(50%-0.375rem)] lg:basis-[calc(33.333%-0.5rem)] flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Total kg</label>
                    <input
                        type="text"
                        :value="formatKg((Number(formByKind.avicola.pesoTotal) || 0) * 20) + ' kg'"
                        readonly
                        class="w-full rounded-lg border border-slate-300 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700"
                    >
                </div>
                <div class="min-w-0 basis-full sm:basis-[calc(50%-0.375rem)] lg:basis-[calc(33.333%-0.5rem)] flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" x-text="activeTab === 'avicola' ? 'Precio compra por cajón' : 'Precio compra por kg'"></label>
                    <input x-model.number="formByKind[activeTab].precioCompra" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="0.00">
                </div>
                <div class="min-w-0 basis-full sm:basis-[calc(50%-0.375rem)] lg:basis-[calc(33.333%-0.5rem)] flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Total compra</label>
                    <input type="text" :value="'$ ' + formatMoney(totalCompra())" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700">
                </div>
            </div>
        </div>

        <div class="mt-5">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 text-sm">
                    <button
                        type="button"
                        @click="cutsViewTab = 'activos'"
                        :class="cutsViewTab === 'activos' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'"
                        class="rounded-lg px-3 py-1.5 font-medium transition"
                    >
                        Activos (<span x-text="activeRowsCount()"></span>)
                    </button>
                    <button
                        type="button"
                        @click="cutsViewTab = 'desactivados'"
                        :class="cutsViewTab === 'desactivados' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'"
                        class="rounded-lg px-3 py-1.5 font-medium transition"
                    >
                        Cortes desactivados (<span x-text="inactiveRowsCount()"></span>)
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr class="text-left text-slate-700">
                            <th class="px-3 py-2 font-semibold">Corte</th>
                            <th class="px-3 py-2 font-semibold">Peso (kg)</th>
                            <th class="px-3 py-2 font-semibold" x-text="activeTab === 'avicola' ? 'Precio por unidad' : 'Precio por kg'"></th>
                            <th class="px-3 py-2 font-semibold">Subtotal</th>
                            <th class="px-3 py-2 font-semibold text-slate-500">Últ. precio kg</th>
                            <th class="px-3 py-2 font-semibold text-indigo-600">Inc. kg %</th>
                            <th class="px-3 py-2 font-semibold text-indigo-600">Inc. $ %</th>
                            <th class="px-3 py-2 font-semibold">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in visibleRows()" :key="`${activeTab}-${row.id}`">
                            <tr class="border-t border-slate-200" :class="row.activo ? '' : 'bg-slate-50 text-slate-400'">
                                <td class="px-3 py-2">
                                    <span class="font-medium" :class="row.activo ? 'text-slate-900' : 'text-slate-500'" x-text="row.nombre"></span>
                                </td>
                                <td class="px-3 py-2">
                                    <input x-model.number="row.peso" type="number" min="0" step="0.001" :disabled="!row.activo" class="w-32 rounded-md border border-slate-300 px-2 py-1.5 text-sm disabled:bg-slate-100 disabled:text-slate-400">
                                </td>
                                <td class="px-3 py-2">
                                    <input x-model.number="row.precio" type="number" min="0" step="0.01" :disabled="!row.activo" class="w-32 rounded-md border border-slate-300 px-2 py-1.5 text-sm disabled:bg-slate-100 disabled:text-slate-400">
                                </td>
                                <td class="px-3 py-2 font-semibold" :class="row.activo ? 'text-slate-900' : 'text-slate-500'">
                                    $ <span x-text="formatMoney(row.activo ? rowSubtotal(row) : 0)"></span>
                                </td>
                                <td class="px-3 py-2">
                                    <template x-if="row.ultimoPrecio !== null">
                                        <span
                                            class="text-xs font-medium"
                                            :class="row.precio > 0 && row.precio > row.ultimoPrecio ? 'text-rose-600' : (row.precio > 0 && row.precio < row.ultimoPrecio ? 'text-emerald-600' : 'text-slate-500')"
                                            x-text="'$ ' + formatMoney(row.ultimoPrecio)"
                                        ></span>
                                    </template>
                                    <template x-if="row.ultimoPrecio === null">
                                        <span class="text-xs text-slate-400">—</span>
                                    </template>
                                </td>
                                <td class="px-3 py-2">
                                    <template x-if="cantidadBaseKg() > 0 && row.activo && (Number(row.peso) || 0) > 0">
                                        <span class="text-xs font-semibold text-indigo-700"
                                            x-text="((Number(row.peso) / cantidadBaseKg()) * 100).toFixed(1) + '%'"
                                        ></span>
                                    </template>
                                    <template x-if="!(cantidadBaseKg() > 0 && row.activo && (Number(row.peso) || 0) > 0)">
                                        <span class="text-xs text-slate-400">—</span>
                                    </template>
                                </td>
                                <td class="px-3 py-2">
                                    <template x-if="totalCompra() > 0 && row.activo && rowSubtotal(row) > 0">
                                        <span class="text-xs font-semibold text-indigo-700"
                                            x-text="((rowSubtotal(row) / totalCompra()) * 100).toFixed(1) + '%'"
                                        ></span>
                                    </template>
                                    <template x-if="!(totalCompra() > 0 && row.activo && rowSubtotal(row) > 0)">
                                        <span class="text-xs text-slate-400">—</span>
                                    </template>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="row.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'" x-text="row.activo ? 'Activo' : 'Inactivo'"></span>
                                        <button
                                            type="button"
                                            x-show="row.activo"
                                            @click="if (confirm('Se dará de baja este corte del catálogo. Continuar?')) { $wire.darBajaCorteCatalogo(row.id) }"
                                            class="rounded-md border border-amber-300 px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-50"
                                        >
                                            Dar de baja
                                        </button>
                                        <button
                                            type="button"
                                            x-show="!row.activo"
                                            @click="$wire.darAltaCorteCatalogo(row.id)"
                                            class="rounded-md border border-emerald-300 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50"
                                        >
                                            Dar de alta
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="visibleRows().length === 0">
                            <td colspan="6" class="px-3 py-6 text-center text-sm text-slate-500">
                                No hay cortes para esta pestaña en esta categoría.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5 flex gap-3 overflow-x-auto">
            <div class="flex-1 rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-slate-500">Peso total cortes</p>
                <p class="mt-1 text-lg font-semibold text-slate-900"><span x-text="formatKg(totalPesoCortes())"></span> kg</p>
            </div>
            <div class="flex-1 rounded-lg border px-4 py-3"
                :class="alertaRendimientoBajo()
                    ? 'border-red-300 bg-red-50'
                    : 'border-slate-200 bg-white'">
                <p class="text-xs uppercase tracking-wide"
                    :class="alertaRendimientoBajo() ? 'text-red-600' : 'text-slate-500'">Rendimiento kg</p>
                <p class="mt-1 text-lg font-semibold"
                    :class="alertaRendimientoBajo() ? 'text-red-700' : 'text-indigo-700'">
                    <span x-text="new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rendimientoPct())"></span>%
                </p>
                <p x-show="alertaRendimientoBajo()"
                    class="mt-1 flex items-center gap-1 text-xs font-semibold text-red-600">
                    <svg class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                    Rendimiento bajo el 70%
                </p>
            </div>
            <div class="flex-1 rounded-lg border border-violet-200 bg-violet-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-violet-600">Rendimiento económico</p>
                <p class="mt-1 text-lg font-semibold text-violet-800"><span x-text="new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rendimientoEconomico())"></span>%</p>
            </div>
            <div class="flex-1 rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total compra</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">$ <span x-text="formatMoney(totalCompra())"></span></p>
            </div>
            <div class="flex-1 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-indigo-700">Total cortes</p>
                <p class="mt-1 text-lg font-semibold text-indigo-900">$ <span x-text="formatMoney(totalValorCortes())"></span></p>
            </div>
            <div class="flex-1 rounded-lg border px-4 py-3"
                :class="diferenciaKg() >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'">
                <p class="text-xs uppercase tracking-wide"
                    :class="diferenciaKg() >= 0 ? 'text-emerald-700' : 'text-red-700'">Diferencia kg</p>
                <p class="mt-1 text-lg font-semibold"
                    :class="diferenciaKg() >= 0 ? 'text-emerald-900' : 'text-red-900'">
                    <span x-text="(diferenciaKg() >= 0 ? '+' : '') + formatKg(diferenciaKg())"></span> kg
                </p>
            </div>
            <div class="flex-1 rounded-lg border px-4 py-3"
                :class="diferenciaValor() >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'">
                <p class="text-xs uppercase tracking-wide"
                    :class="diferenciaValor() >= 0 ? 'text-emerald-700' : 'text-red-700'">Diferencia $</p>
                <p class="mt-1 text-lg font-semibold"
                    :class="diferenciaValor() >= 0 ? 'text-emerald-900' : 'text-red-900'">
                    <span x-text="(diferenciaValor() >= 0 ? '+ $ ' : '- $ ') + formatMoney(Math.abs(diferenciaValor()))"></span>
                </p>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Margen objetivo</label>                <select x-model="margenTipo" class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <option value="margen">Margen</option>
                    <option value="markup">Mark Up</option>
                </select>
                <div class="flex items-center overflow-hidden rounded-lg border border-slate-300 bg-white">
                    <input x-model.number="margenPct" type="number" min="0" max="999" step="0.5" class="w-20 px-3 py-1.5 text-sm text-slate-900 focus:outline-none">
                    <span class="border-l border-slate-300 bg-slate-100 px-2 py-1.5 text-sm font-semibold text-slate-600">%</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs uppercase tracking-wide text-slate-500">Rendimiento económico</span>
                <span class="text-sm font-bold text-violet-700" x-text="new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rendimientoEconomico()) + '%'"></span>
            </div>
            <template x-if="margenIndicador()">
                <div class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold"
                    :class="{
                        'bg-emerald-100 text-emerald-800': rendimientoEconomico() - margenObjetivo() > 0.5,
                        'bg-red-100 text-red-700': rendimientoEconomico() - margenObjetivo() < -0.5,
                        'bg-slate-200 text-slate-700': Math.abs(rendimientoEconomico() - margenObjetivo()) < 0.5
                    }">
                    <span x-text="margenIndicador().icon" class="text-base leading-none"></span>
                    <span x-text="margenIndicador().label"></span>
                </div>
            </template>
        </div>

        <div class="mt-3 space-y-2">
            <div
                x-show="alertaRendimientoBajo()"
                class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700"
            >
                Advertencia: el rendimiento en kg está por debajo del 70%.
            </div>
            <div
                x-show="alertaRentabilidadBaja()"
                class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800"
                x-text="textoAlertaRentabilidad()"
            ></div>
        </div>

        <div class="mt-4 flex w-full justify-end gap-2">
            <button
                type="button"
                @click="dejarPendiente()"
                class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100"
            >
                Dejar pendiente
            </button>
            <button
                type="button"
                @click="openGuardarModal(false)"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Guardar desposte
            </button>
            <button
                type="button"
                @click="openGuardarModal(true)"
                class="rounded-lg border border-indigo-400 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
            >
                Guardar e imprimir
            </button>
        </div>

        <div
            x-show="showGuardarModal"
            x-transition
            @keydown.escape.window="closeGuardarModal()"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center"
        >
            <div class="absolute inset-0 bg-black/50" @click="closeGuardarModal()"></div>
            <div class="relative z-10 my-4 w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl sm:my-0">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900" x-text="guardarImprimir ? 'Guardar e imprimir' : 'Guardar desposte'"></h3>
                        <p class="mt-1 text-sm text-slate-600">Resumen del desposte de <span class="font-semibold" x-text="tabTitle(activeTab)"></span></p>
                    </div>
                    <button type="button" @click="closeGuardarModal()" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cerrar</button>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <pre class="whitespace-pre-wrap text-xs text-slate-700 font-mono leading-relaxed" x-text="resumenTexto()"></pre>
                </div>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Número de WhatsApp (opcional)</label>
                    <input
                        x-model="whatsappTel"
                        type="tel"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                        placeholder="Ej: 5491112345678 (con código de país, sin +)"
                    >
                    <p class="mt-1 text-xs text-slate-500">Dejalo vacío para abrir WhatsApp Web sin destinatario y elegir vos.</p>
                </div>

                <div x-show="alertasGuardado().length" class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-800">Advertencias al guardar</p>
                    <ul class="mt-1 list-disc space-y-1 pl-4 text-xs font-medium text-amber-800">
                        <template x-for="(item, idx) in alertasGuardado()" :key="idx">
                            <li x-text="item"></li>
                        </template>
                    </ul>
                </div>

                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        @click="enviarWhatsApp(whatsappTel)"
                        class="inline-flex items-center gap-2 rounded-lg border border-indigo-400 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Enviar por WhatsApp
                    </button>
                    <button
                        x-show="guardarImprimir"
                        type="button"
                        @click="confirmarGuardado()"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Guardar e imprimir
                    </button>
                    <button
                        x-show="!guardarImprimir"
                        type="button"
                        @click="confirmarGuardado()"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Guardar desposte
                    </button>
                    <button
                        type="button"
                        @click="closeGuardarModal()"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

    </section>
</div>
