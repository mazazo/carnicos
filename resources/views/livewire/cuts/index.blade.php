<x-layouts.app>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Cortes</h1>
        <a href="{{ route('cuts.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Nuevo corte</a>
    </div>

    @if (session('success'))
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Buscar por corte, tipo o fecha"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
        >

        <div class="mt-4 overflow-x-auto">
        </div>

        {{-- Mobile card list --}}
        <div class="mt-4 space-y-3 sm:hidden">
            @forelse ($cuts as $cut)
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-medium">{{ $cut->nombre }}</p>
                        <span class="text-xs font-semibold text-slate-900">${{ number_format((float) $cut->peso * (float) ($cut->precio_kg ?? 0), 2) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 capitalize">{{ $cut->animal->animalType->nombre }} · {{ $cut->animal->fecha->format('d/m/Y') }}</p>
                    <div class="mt-2 flex gap-4 text-xs text-slate-600">
                        <span>Peso: <strong>{{ number_format((float) $cut->peso, 3) }} kg</strong></span>
                        <span>Precio kg: <strong>${{ number_format((float) ($cut->precio_kg ?? 0), 2) }}</strong></span>
                    </div>
                    <div class="mt-3 flex gap-3 border-t border-slate-100 pt-3 text-sm">
                        <a href="{{ route('cuts.edit', $cut) }}" class="font-medium text-slate-900 underline">Editar</a>
                        <button
                            type="button"
                            wire:click="deleteCut({{ $cut->id }})"
                            wire:confirm="Se eliminara este corte. Continuar?"
                            class="font-medium text-red-700 underline"
                        >Eliminar</button>
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-slate-500">No hay cortes registrados.</p>
            @endforelse
        </div>

        {{-- Desktop table --}}
        <div class="mt-4 hidden overflow-x-auto sm:block">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-slate-600">
                        <th class="py-2">Corte</th>
                        <th class="py-2">Animal</th>
                        <th class="py-2">Peso</th>
                        <th class="py-2">Precio kg</th>
                        <th class="py-2">Valor</th>
                        <th class="py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cuts as $cut)
                        <tr class="border-b">
                            <td class="py-2">{{ $cut->nombre }}</td>
                            <td class="py-2">
                                <div class="capitalize">{{ $cut->animal->animalType->nombre }}</div>
                                <div class="text-xs text-slate-500">{{ $cut->animal->fecha->format('Y-m-d') }}</div>
                            </td>
                            <td class="py-2">{{ number_format((float) $cut->peso, 3) }} kg</td>
                            <td class="py-2">${{ number_format((float) ($cut->precio_kg ?? 0), 2) }}</td>
                            <td class="py-2">${{ number_format((float) $cut->peso * (float) ($cut->precio_kg ?? 0), 2) }}</td>
                            <td class="py-2">
                                <div class="flex gap-2">
                                    <a href="{{ route('cuts.edit', $cut) }}" class="text-slate-900 underline">Editar</a>
                                    <button
                                        type="button"
                                        wire:click="deleteCut({{ $cut->id }})"
                                        wire:confirm="Se eliminara este corte. Continuar?"
                                        class="text-red-700 underline"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-center text-slate-500">No hay cortes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $cuts->links() }}</div>
    </div>
</x-layouts.app>
