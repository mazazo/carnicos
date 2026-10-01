<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('admin.config.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Configuracion</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Carnicerias</h1>
            <p class="mt-1 text-sm text-slate-600">Prueba, plan, vencimiento y pagos de cada cliente.</p>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <input wire:model.live.debounce.400ms="busqueda" type="search" placeholder="Buscar por nombre, CUIT o email"
               class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm sm:max-w-sm">
        <select wire:model.live="estado" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <option value="">Todas</option>
            <option value="prueba">En prueba</option>
            <option value="activa">Con plan activo</option>
            <option value="sin_acceso">Sin acceso (vencidas)</option>
            <option value="pago_pendiente">Con pago pendiente</option>
            <option value="suspendida">Suspendidas</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Carniceria</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Vence</th>
                    <th class="px-4 py-3 text-center">Usuarios</th>
                    <th class="px-4 py-3 text-center">Pagos pend.</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($carnicerias as $carniceria)
                    @php($vigente = $carniceria->suscripciones->sortByDesc('ends_at')->first())
                    <tr wire:key="carniceria-{{ $carniceria->id }}">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-900">{{ $carniceria->nombre }}</p>
                            <p class="text-xs text-slate-500">{{ $carniceria->dueno?->email }}</p>
                        </td>
                        <td class="px-4 py-3">
                            @if (! $carniceria->estaActiva())
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Suspendida</span>
                            @elseif (! $vigente)
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-700">Sin acceso</span>
                            @elseif ($vigente->isTrial())
                                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700">Prueba</span>
                            @else
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Activa</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $vigente?->planModel?->nombre ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-700">
                            @if ($vigente)
                                {{ $vigente->ends_at->format('d/m/Y') }}
                                <span class="text-xs text-slate-500">({{ $vigente->diasRestantes() }} d)</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ $carniceria->usuarios_count }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($carniceria->pagos_pendientes_count)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">{{ $carniceria->pagos_pendientes_count }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.carnicerias.show', $carniceria) }}" class="text-sm font-semibold text-amber-700 hover:text-amber-900">Gestionar &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No hay carnicerias con ese filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $carnicerias->links() }}
</div>
