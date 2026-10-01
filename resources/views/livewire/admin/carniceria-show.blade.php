<div class="space-y-6">
    <div>
        <a href="{{ route('admin.carnicerias.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Carnicerias</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold text-slate-900">{{ $carniceria->nombre }}</h1>
            @if (! $carniceria->estaActiva())
                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Suspendida</span>
            @endif
        </div>
        <p class="mt-1 text-sm text-slate-600">
            {{ $carniceria->cuit ? 'CUIT '.$carniceria->cuit.' · ' : '' }}Alta {{ $carniceria->created_at?->format('d/m/Y') }}
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Acceso actual</p>
        @if ($vigente)
            <p class="mt-1 text-lg font-bold text-slate-900">
                {{ $vigente->planModel?->nombre }} · {{ $vigente->etiquetaEstado() }}
            </p>
            <p class="text-sm text-slate-600">Vence el {{ $vigente->ends_at->format('d/m/Y H:i') }} ({{ $vigente->diasRestantes() }} dias)</p>
        @else
            <p class="mt-1 text-lg font-bold text-red-700">Sin acceso (bloqueada)</p>
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <form wire:submit="sumarDias" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-bold text-slate-900">Sumar dias</h2>
            <p class="text-xs text-slate-500">Se suman al vencimiento actual. Si esta vencida, desde hoy.</p>
            <input wire:model="dias" type="number" min="1" max="365" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
            @error('dias') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <button class="w-full rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500">Sumar dias</button>
        </form>

        <form wire:submit="habilitarPlan" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-bold text-slate-900">Habilitar plan (sin pago)</h2>
            <select wire:model="planId" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                @foreach ($planes as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->nombre }}{{ $plan->activo ? '' : ' (inactivo)' }}</option>
                @endforeach
            </select>
            <div class="grid grid-cols-2 gap-2">
                <label class="text-xs text-slate-600">Meses
                    <input wire:model="planMeses" type="number" min="0" max="36" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="text-xs text-slate-600">Dias
                    <input wire:model="planDias" type="number" min="0" max="365" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
            </div>
            @error('planMeses') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <button class="w-full rounded-xl bg-amber-500 px-4 py-2 text-sm font-bold text-amber-950 hover:bg-amber-400">Habilitar</button>
        </form>

        <form wire:submit="registrarPago" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-bold text-slate-900">Registrar pago recibido</h2>
            <select wire:model="pagoPlanId" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                @foreach ($planes as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->nombre }}</option>
                @endforeach
            </select>
            <div class="grid grid-cols-2 gap-2">
                <label class="text-xs text-slate-600">Meses
                    <input wire:model="pagoMeses" type="number" min="1" max="36" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="text-xs text-slate-600">Monto
                    <input wire:model="pagoMonto" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
            </div>
            <select wire:model="pagoMetodo" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <option value="transferencia">Transferencia</option>
                <option value="efectivo">Efectivo</option>
                <option value="otro">Otro</option>
            </select>
            <input wire:model="pagoReferencia" type="text" placeholder="Referencia (opcional)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
            @error('pagoMonto') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @error('pagoMeses') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <button class="w-full rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Registrar y activar</button>
        </form>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <form wire:submit="guardarTipos" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-bold text-slate-900">Tipos de animal</h2>
            <p class="text-xs text-slate-500">Plan actual: {{ $vigente?->planModel?->max_tipos_animal ? $vigente->planModel->max_tipos_animal.' tipo(s)' : 'sin limite' }}.</p>
            <div class="flex flex-wrap gap-3">
                @foreach ($tiposDisponibles as $tipo)
                    <label wire:key="tipo-{{ $tipo->id }}" class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="tipos" value="{{ $tipo->id }}" class="rounded text-amber-600">
                        {{ $tipo->nombre }}
                    </label>
                @endforeach
            </div>
            @error('tipos') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Guardar tipos</button>
        </form>

        <div class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-bold text-slate-900">Suspender / reactivar</h2>
            <p class="text-xs text-slate-500">Suspendida no entra al sistema aunque tenga plan vigente.</p>
            @if ($carniceria->estaActiva())
                <input wire:model="motivo" type="text" placeholder="Motivo (tambien se usa al rechazar un pago)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                @error('motivo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <button wire:click="suspender" wire:confirm="¿Suspender {{ $carniceria->nombre }}?" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">Suspender</button>
            @else
                <button wire:click="reactivar" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Reactivar</button>
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <h2 class="border-b border-slate-100 px-5 py-3 font-bold text-slate-900">Pagos</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-2">#</th><th class="px-4 py-2">Fecha</th><th class="px-4 py-2">Plan</th><th class="px-4 py-2">Monto</th>
                        <th class="px-4 py-2">Medio</th><th class="px-4 py-2">Estado</th><th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pagos as $pago)
                        <tr wire:key="pago-{{ $pago->id }}">
                            <td class="px-4 py-2 text-slate-500">{{ $pago->id }}</td>
                            <td class="px-4 py-2">{{ $pago->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $pago->plan?->nombre }} · {{ $pago->meses }}m</td>
                            <td class="px-4 py-2">${{ number_format((float) $pago->amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-2">{{ $pago->method }}{{ $pago->reference ? ' · '.$pago->reference : '' }}</td>
                            <td class="px-4 py-2">{{ $pago->etiquetaEstado() }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-right">
                                @if ($pago->status === 'pending')
                                    <button wire:click="aprobarPago({{ $pago->id }})" wire:confirm="¿Aprobar el pago #{{ $pago->id }} y activar el plan?" class="text-xs font-semibold text-emerald-700 hover:underline">Aprobar</button>
                                    <button wire:click="rechazarPago({{ $pago->id }})" wire:confirm="¿Rechazar el pago #{{ $pago->id }}?" class="ml-2 text-xs font-semibold text-red-700 hover:underline">Rechazar</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Sin pagos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-100 px-5 py-3 font-bold text-slate-900">Usuarios</h2>
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($usuarios as $usuario)
                    <li wire:key="usuario-{{ $usuario->id }}" class="flex justify-between px-5 py-2">
                        <span>{{ $usuario->full_name }} <span class="text-xs text-slate-500">{{ $usuario->email }}</span></span>
                        <span class="text-xs font-semibold uppercase text-slate-500">{{ $usuario->rol }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-100 px-5 py-3 font-bold text-slate-900">Historial</h2>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($movimientos as $movimiento)
                    <li wire:key="mov-{{ $movimiento->id }}" class="px-5 py-2">
                        <p class="text-slate-800">{{ $movimiento->detalle }}</p>
                        <p class="text-xs text-slate-500">{{ $movimiento->created_at->format('d/m/Y H:i') }} · {{ $movimiento->user?->full_name ?? 'Sistema' }}</p>
                    </li>
                @empty
                    <li class="px-5 py-4 text-center text-slate-500">Sin movimientos.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
