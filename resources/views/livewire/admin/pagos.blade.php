<div class="space-y-6">
    <div>
        <a href="{{ route('admin.config.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Configuracion</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Pagos</h1>
        <p class="mt-1 text-sm text-slate-600">Aprobar un pago activa el plan por los meses pagados (si ya tenia ese plan, se suma al vencimiento).</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row">
        <select wire:model.live="estado" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <option value="pending">Pendientes</option>
            <option value="paid">Pagados</option>
            <option value="failed">Rechazados</option>
            <option value="refunded">Devueltos</option>
            <option value="">Todos</option>
        </select>
        @if ($estado === 'pending')
            <input wire:model="motivo" type="text" placeholder="Motivo al rechazar (opcional)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm sm:max-w-sm">
        @endif
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">#</th><th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Carniceria</th><th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Monto</th><th class="px-4 py-3">Medio</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($pagos as $pago)
                    <tr wire:key="pago-{{ $pago->id }}">
                        <td class="px-4 py-3 text-slate-500">{{ $pago->id }}</td>
                        <td class="px-4 py-3">{{ $pago->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            @if ($pago->carniceria)
                                <a href="{{ route('admin.carnicerias.show', $pago->carniceria) }}" class="font-semibold text-slate-900 hover:text-amber-700">{{ $pago->carniceria->nombre }}</a>
                            @endif
                            <p class="text-xs text-slate-500">{{ $pago->user?->email }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $pago->plan?->nombre }} · {{ $pago->meses }} {{ $pago->meses === 1 ? 'mes' : 'meses' }}</td>
                        <td class="px-4 py-3">${{ number_format((float) $pago->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $pago->method }}{{ $pago->reference ? ' · '.$pago->reference : '' }}</td>
                        <td class="px-4 py-3">{{ $pago->etiquetaEstado() }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @if ($pago->status === 'pending')
                                <button wire:click="aprobar({{ $pago->id }})" wire:confirm="¿Aprobar el pago #{{ $pago->id }} y activar el plan?" class="text-xs font-semibold text-emerald-700 hover:underline">Aprobar</button>
                                <button wire:click="rechazar({{ $pago->id }})" wire:confirm="¿Rechazar el pago #{{ $pago->id }}?" class="ml-2 text-xs font-semibold text-red-700 hover:underline">Rechazar</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">No hay pagos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $pagos->links() }}
</div>
