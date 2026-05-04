<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-600">Vista compacta para operación diaria con foco en carga rápida y actividad reciente.</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2.5 rounded-2xl bg-gradient-to-r from-amber-100 via-amber-50 to-yellow-100 px-6 py-3 shadow-lg ring-2 ring-amber-300/60">
                <svg class="h-6 w-6 text-amber-600 drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                <div class="flex flex-col leading-none">
                    <span class="text-lg font-black uppercase tracking-widest text-amber-800 drop-shadow-sm">Pro</span>
                    <span class="tg-widest text-amber-600">Service</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-[1.4fr_1fr]">
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Resumen operativo</p>
                <div class="flex items-center gap-2">
                    <label for="rango-resumen" class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Rango</label>
                    <select id="rango-resumen" wire:model.change="rangoResumen" class="rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-sm font-medium text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                        <option value="dia">Día</option>
                        <option value="semana">Semana</option>
                        <option value="mes">Mes</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex gap-4">
                <div class="flex-1">
                    <p class="text-xs text-emerald-600">Reses vacunas</p>
                    <p class="mt-0.5 text-2xl font-bold text-emerald-900">{{ $vacuno_count }}</p>
                </div>
                <div class="flex-1">
                    <p class="text-xs text-emerald-600">Reses de cerdo</p>
                    <p class="mt-0.5 text-2xl font-bold text-emerald-900">{{ $porcino_count }}</p>
                </div>
                <div class="flex-1">
                    <p class="text-xs text-emerald-600">Cajones avícolas</p>
                    <p class="mt-0.5 text-2xl font-bold text-emerald-900">{{ $cajones_count }}</p>
                </div>
                <div class="flex-1">
                    <p class="text-xs text-emerald-600">Total kg procesados</p>
                    <p class="mt-0.5 text-2xl font-bold text-emerald-900">{{ number_format((float) $total_kg_procesados, 1) }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Actividad reciente</h2>
            <div class="mt-4 space-y-3">
                @php
                    $ultimos = collect([$vacuno_animals, $porcino_animals, $avicola_animals])
                        ->flatten(1)
                        ->sortByDesc(fn ($animal) => $animal->fecha->timestamp . str_pad((string) $animal->id, 10, '0', STR_PAD_LEFT))
                        ->take(6);
                @endphp

                @forelse($ultimos as $animal)
                    <a href="{{ route('animals.show', $animal) }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 hover:bg-slate-50">
                        <div>
                            <p class="font-medium capitalize text-slate-900">{{ $animal->animalType->nombre }}</p>
                            <p class="text-xs text-slate-500">{{ $animal->fecha->format('Y-m-d') }} · {{ number_format((float) $animal->peso_total, 3) }} {{ str_contains(strtolower($animal->animalType->nombre), 'avi') || str_contains(strtolower($animal->animalType->nombre), 'pollo') ? 'cant.' : 'kg' }}</p>
                        </div>
                        <span class="text-sm font-semibold text-slate-700">{{ $animal->cuts_count }} cortes</span>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
                        Todavía no hay ingresos cargados.
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <section
        class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        x-data="{
            activeTab: 'vacuno',
            cutCatalog: @js($cut_catalog_by_kind ?? []),
            formByKind: {
                vacuno: { cantidad: 1, pesoTotal: 0, frigorifico: '', precioCompra: 0 },
                porcino: { cantidad: 1, pesoTotal: 0, frigorifico: '', precioCompra: 0 },
                avicola: { cantidad: 1, pesoTotal: 0, frigorifico: '', precioCompra: 0 },
            },
            rowsByKind: {},
            cutsViewTab: 'activos',
            showAddCutModal: false,
            newCutNombre: '',
            newCutCantidad: 1,
            margenPct: 30,
            margenTipo: 'margen',
            init() {
                const kinds = ['vacuno', 'porcino', 'avicola'];
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
            },
            tabTitle(kind) {
                if (kind === 'vacuno') return 'Vacuno';
                if (kind === 'porcino') return 'Porcino';
                return 'Avícola';
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
            openAddCutModal() {
                this.newCutNombre = '';
                this.newCutCantidad = 1;
                this.showAddCutModal = true;
            },
            closeAddCutModal() {
                this.showAddCutModal = false;
            },
            submitAddCut() {
                const nombre = String(this.newCutNombre || '').trim();
                const cantidad = Number(this.newCutCantidad || 1);
                if (!nombre) {
                    alert('Ingresá el nombre del corte.');
                    return;
                }

                $wire.agregarCorteCatalogo(this.activeTab, nombre, cantidad)
                    .then(() => {
                        this.closeAddCutModal();
                    })
                    .catch(() => {
                        alert('No se pudo guardar el corte. Revisá los datos e intentá de nuevo.');
                    });
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
            rendimiento(kind = null) {
                const key = kind || this.activeTab;
                const data = this.formByKind[key] || {};
                const pesoBase = Number(data.pesoTotal) || 0;
                if (!pesoBase) return 0;
                return (this.totalPesoCortes(key) / pesoBase) * 100;
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
                <h2 class="text-lg font-semibold text-slate-900">Carga rápida por tipo</h2>
                <p class="mt-1 text-sm text-slate-600">Completá ingreso y cortes en una sola vista con rendimiento estimado.</p>
            </div>
            <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 text-sm">
                <button type="button" @click="activeTab = 'vacuno'" :class="activeTab === 'vacuno' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'" class="rounded-lg px-3 py-1.5 font-medium transition">Vacuno</button>
                <button type="button" @click="activeTab = 'porcino'" :class="activeTab === 'porcino' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'" class="rounded-lg px-3 py-1.5 font-medium transition">Porcino</button>
                <button type="button" @click="activeTab = 'avicola'" :class="activeTab === 'avicola' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'" class="rounded-lg px-3 py-1.5 font-medium transition">Avícola</button>
            </div>
        </div>

        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-nowrap gap-3 overflow-x-auto pb-1">
                <div class="min-w-52.5 flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Frigorífico</label>
                    <input x-model="formByKind[activeTab].frigorifico" type="text" maxlength="150" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Nombre del frigorífico">
                </div>
                <div class="min-w-45 flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Tipo de ingreso</label>
                    <input type="text" :value="tabTitle(activeTab)" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700">
                </div>
                <div class="min-w-47.5 flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Peso total (kg)</label>
                    <input x-model.number="formByKind[activeTab].pesoTotal" type="number" min="0" step="0.001" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="0.000">
                </div>
                <div class="min-w-47.5 flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Precio compra por kg</label>
                    <input x-model.number="formByKind[activeTab].precioCompra" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="0.00">
                </div>
                <div class="min-w-47.5 flex-1">
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
                <button
                    type="button"
                    @click="openAddCutModal()"
                    class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100"
                >
                    + Agregar corte
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr class="text-left text-slate-700">
                            <th class="px-3 py-2 font-semibold">Corte</th>
                            <th class="px-3 py-2 font-semibold">Peso (kg)</th>
                            <th class="px-3 py-2 font-semibold">Precio por kg</th>
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
                                    <template x-if="(Number(formByKind[activeTab]?.pesoTotal) || 0) > 0 && row.activo && (Number(row.peso) || 0) > 0">
                                        <span class="text-xs font-semibold text-indigo-700"
                                            x-text="((Number(row.peso) / Number(formByKind[activeTab].pesoTotal)) * 100).toFixed(1) + '%'"
                                        ></span>
                                    </template>
                                    <template x-if="!((Number(formByKind[activeTab]?.pesoTotal) || 0) > 0 && row.activo && (Number(row.peso) || 0) > 0)">
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
            <div class="flex-1 rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-slate-500">Rendimiento kg</p>
                <p class="mt-1 text-lg font-semibold text-emerald-700"><span x-text="new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rendimiento())"></span>%</p>
            </div>
            <div class="flex-1 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-sky-600">Rendimiento económico</p>
                <p class="mt-1 text-lg font-semibold text-sky-800"><span x-text="new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rendimientoEconomico())"></span>%</p>
            </div>
            <div class="flex-1 rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total compra</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">$ <span x-text="formatMoney(totalCompra())"></span></p>
            </div>
            <div class="flex-1 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-emerald-700">Total cortes</p>
                <p class="mt-1 text-lg font-semibold text-emerald-900">$ <span x-text="formatMoney(totalValorCortes())"></span></p>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Margen objetivo</label>
                <select x-model="margenTipo" class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400">
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
                <span class="text-sm font-bold text-sky-700" x-text="new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rendimientoEconomico()) + '%'"></span>
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

        <div
            x-show="showAddCutModal"
            x-transition
            @keydown.escape.window="closeAddCutModal()"
            class="fixed inset-0 z-9999 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div class="absolute inset-0 bg-black/50" @click="closeAddCutModal()"></div>
            <div class="relative z-10 w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Agregar corte</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            Se agregará a la categoría <span class="font-semibold" x-text="tabTitle(activeTab)"></span>.
                        </p>
                    </div>
                    <button type="button" @click="closeAddCutModal()" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cerrar</button>
                </div>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Nombre del corte</label>
                        <input x-model="newCutNombre" type="text" maxlength="120" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Ej: Bife angosto">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Cantidad esperada</label>
                        <input x-model.number="newCutCantidad" type="number" min="1" step="1" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="1">
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="closeAddCutModal()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button type="button" @click="submitAddCut()" class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Guardar corte</button>
                </div>
            </div>
        </div>
    </section>
</div>
