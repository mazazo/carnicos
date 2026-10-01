<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Tipos de animal</h1>
        <p class="text-sm text-slate-600">
            Tu plan <strong>{{ $plan?->nombre }}</strong> incluye
            {{ $plan?->max_tipos_animal === 1 ? '1 tipo de animal' : ($plan?->max_tipos_animal.' tipos de animal') }}.
            Elegí con cuál{{ $plan?->max_tipos_animal === 1 ? '' : 'es' }} trabajás.
        </p>
    </div>

    @if ($puedeElegir)
        <form wire:submit="guardar" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ($tipos as $tipo)
                    <label wire:key="tipo-{{ $tipo->id }}" class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 hover:border-amber-300 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
                        <input type="checkbox" wire:model="seleccion" value="{{ $tipo->id }}" class="h-4 w-4 rounded border-slate-300 text-amber-600">
                        <span class="font-semibold capitalize text-slate-800">{{ $tipo->nombre }}</span>
                    </label>
                @endforeach
            </div>
            @error('seleccion') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <p class="text-xs text-slate-500">Después, para cambiarlo, pedíselo a administración.</p>
            <button class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-amber-950 hover:bg-amber-400">Guardar</button>
        </form>
    @elseif ($debeElegir)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
            El dueño de la carnicería tiene que elegir los tipos de animal antes de seguir.
        </div>
    @else
        <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
            Tu carnicería ya tiene sus tipos de animal. Para cambiarlos, pedíselo a administración.
        </div>
    @endif
</div>
