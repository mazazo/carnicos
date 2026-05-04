<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-700">Configuracion admin</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Cortes asociados por tipo de animal</h1>
            <p class="mt-1 text-sm text-slate-600">Auditoria visual para detectar tipos de cortes mal asociados.</p>
        </div>
        <a href="{{ route('dashboard.pro') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Volver al dashboard
        </a>
    </div>

    <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Alerta de consistencia</p>
        <p class="mt-1 text-lg font-bold text-amber-900">Registros de cortes con tipo distinto al animal: {{ $mismatchCount }}</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($animalTypes as $type)
            @php
                $globalCuts = $type->cutCatalogs->whereNull('user_id');
                $userCuts = $type->cutCatalogs->whereNotNull('user_id');
                $activeCuts = $type->cutCatalogs->where('activo', true);
            @endphp
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold capitalize text-slate-900">{{ $type->nombre }}</h2>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Modalidad: {{ $type->modalidad }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                        {{ $type->cut_catalogs_count }} cortes
                    </span>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-800">Activos: {{ $activeCuts->count() }}</span>
                    <span class="rounded-full bg-sky-100 px-2.5 py-1 font-semibold text-sky-800">Globales: {{ $globalCuts->count() }}</span>
                    <span class="rounded-full bg-amber-100 px-2.5 py-1 font-semibold text-amber-800">Overrides usuario: {{ $userCuts->count() }}</span>
                </div>

                <div class="mt-4 max-h-64 overflow-y-auto rounded-xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-left text-slate-700">
                            <tr>
                                <th class="px-3 py-2 font-semibold">Corte</th>
                                <th class="px-3 py-2 font-semibold">Origen</th>
                                <th class="px-3 py-2 font-semibold">Estado</th>
                                <th class="px-3 py-2 font-semibold">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($type->cutCatalogs as $cut)
                                <tr class="border-t border-slate-200">
                                    <td class="px-3 py-2 text-slate-800">{{ $cut->nombre_canonico }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $cut->user_id ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' }}">
                                            {{ $cut->user_id ? 'Usuario: ' . ($cut->user?->full_name ?? 'N/A') : 'Global' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $cut->activo ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                            {{ $cut->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        @if ($cut->activo)
                                            <button
                                                type="button"
                                                wire:click="cambiarEstadoCorte({{ $cut->id }}, false)"
                                                class="rounded-md border border-amber-300 px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-50"
                                            >
                                                Desactivar
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="cambiarEstadoCorte({{ $cut->id }}, true)"
                                                class="rounded-md border border-emerald-300 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50"
                                            >
                                                Activar
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-5 text-center text-slate-500">Sin cortes cargados para este tipo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>
</div>
