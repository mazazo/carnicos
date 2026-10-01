<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('billing.plans') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Planes</a>

    <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Plan elegido</p>
        <h1 class="mt-1 text-3xl font-black text-stone-950">{{ $plan->nombre }}</h1>
        <p class="mt-1 text-sm text-stone-600">{{ $plan->descripcion }}</p>
        <ul class="mt-4 grid gap-1 text-sm text-stone-700 sm:grid-cols-2">
            @foreach ($plan->incluye() as $item)
                <li>&check; {{ $item }}</li>
            @endforeach
        </ul>
        @if ($vigente)
            <p class="mt-4 text-xs text-stone-500">
                Hoy tenes {{ $vigente->planModel?->nombre }} hasta el {{ $vigente->ends_at->format('d/m/Y') }}.
                @if ($vigente->plan_id === $plan->id && ! $vigente->isTrial()) Lo que pagues se suma al final. @else El plan nuevo empieza al confirmarse el pago. @endif
            </p>
        @endif
    </div>

    @if (! $esDueno)
        <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">El pago lo hace el dueno de la carniceria.</div>
    @else
        <form wire:submit="pagar" class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <p class="mb-2 text-sm font-semibold text-slate-800">Periodo</p>
                <div class="grid gap-3 sm:grid-cols-4">
                    @foreach ($plan->precios as $opcion)
                        <label wire:key="periodo-{{ $opcion->meses }}" class="cursor-pointer rounded-2xl border border-slate-200 p-4 text-center hover:border-amber-300 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
                            <input type="radio" wire:model.live="meses" value="{{ $opcion->meses }}" class="sr-only">
                            <span class="block text-sm font-semibold text-slate-700">{{ $opcion->meses === 1 ? '1 mes' : $opcion->meses.' meses' }}</span>
                            <span class="block text-lg font-black text-slate-950">${{ number_format((float) $opcion->precio, 0, ',', '.') }}</span>
                        </label>
                    @endforeach
                </div>
                @error('meses') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-slate-800">Como pagas</p>
                @if ($metodos === [])
                    <p class="text-sm text-red-600">No hay medios de pago habilitados. Comunicate con administracion.</p>
                @endif
                <div class="space-y-2">
                    @foreach ($metodos as $id => $etiqueta)
                        <label wire:key="metodo-{{ $id }}" class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:border-amber-300 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
                            <input type="radio" wire:model.live="metodo" value="{{ $id }}" class="h-4 w-4 text-amber-600">
                            <span class="text-sm font-medium text-slate-800">{{ $etiqueta }}</span>
                        </label>
                    @endforeach
                </div>
                @error('metodo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($metodo === 'transferencia')
                <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                    <p>{{ $instruccionesTransferencia }}</p>
                    <label class="mt-3 block text-sm font-medium text-slate-700">Numero de operacion o comprobante</label>
                    <input wire:model="referencia" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    @error('referencia') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                <p class="text-lg font-black text-slate-950">
                    Total: ${{ number_format((float) ($precio?->precio ?? 0), 0, ',', '.') }}
                </p>
                <button wire:loading.attr="disabled" class="rounded-2xl bg-amber-500 px-6 py-3 text-sm font-bold text-amber-950 hover:bg-amber-400 disabled:opacity-60">
                    {{ $metodo === 'mercadopago' ? 'Pagar con Mercado Pago' : 'Avisar transferencia' }}
                </button>
            </div>
        </form>
    @endif
</div>
