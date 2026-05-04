<x-layouts.app>
<div>
    <h1 class="text-2xl font-semibold">{{ $cut ? 'Editar corte' : 'Nuevo corte' }}</h1>

    @if (session('success'))
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="mt-5 grid gap-6 lg:grid-cols-3">
    <form id="cut-form" wire:submit="save" class="mt-5 grid gap-6 lg:grid-cols-3 pb-20 sm:pb-0">
        <div class="lg:col-span-2 space-y-4 rounded-xl border border-slate-200 bg-white p-5 order-2 lg:order-1">
            <div>
                <label class="mb-1 block text-sm font-medium">Animal</label>
                <select wire:model="animal_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Seleccionar</option>
                    @foreach($animals as $animal)
                        <option value="{{ $animal->id }}">
                            {{ ucfirst($animal->animalType->nombre) }} - {{ $animal->fecha->format('Y-m-d') }} - {{ number_format((float) $animal->peso_total, 3) }} kg
                        </option>
                    @endforeach
                </select>
                @error('animal_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Nombre del corte</label>
                <input wire:model="nombre" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Peso (kg)</label>
                    <input wire:model.live="peso" type="number" min="0" step="0.001" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('peso') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Precio kg (opcional)</label>
                    <input wire:model.live="precio_kg" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('precio_kg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="space-y-4 order-1 lg:order-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="text-lg font-semibold">Resumen</h2>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Peso</span>
                        <span class="font-semibold">{{ number_format((float) ($peso !== '' ? $peso : 0), 3) }} kg</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Valor estimado</span>
                        <span class="font-semibold">${{ number_format((float) ($peso !== '' ? $peso : 0) * (float) ($precio_kg !== '' ? $precio_kg : 0), 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hidden sm:block">Guardar</button>
                    <a href="{{ route('cuts.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancelar</a>
                </div>
            </div>
        </div>
    </form>

    {{-- Sticky save for mobile --}}
    <div class="fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200 bg-white px-4 py-3 sm:hidden">
        <button form="cut-form" type="submit" class="w-full rounded-lg bg-slate-900 py-3 text-sm font-semibold text-white">Guardar</button>
    </div>
</div>
</x-layouts.app>
