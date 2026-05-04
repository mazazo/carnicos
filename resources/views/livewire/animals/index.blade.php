<x-layouts.app>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Animales</h1>
        <a href="{{ route('animals.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Nuevo animal</a>
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
            placeholder="Buscar por fecha o tipo"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
        >

        <div class="mt-4 overflow-x-auto">
        </div>

        {{-- Mobile card list (xs – sm) --}}
        <div class="mt-4 space-y-3 sm:hidden">
            @forelse ($animals as $animal)
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-medium capitalize">{{ $animal->animalType->nombre }}</p>
                            <p class="text-xs text-slate-500">{{ $animal->fecha->format('d/m/Y') }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $animal->cuts->count() }} cortes</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-700">Peso: <span class="font-semibold">{{ number_format((float) $animal->peso_total, 3) }} kg</span></p>
                    <div class="mt-3 flex gap-3 border-t border-slate-100 pt-3 text-sm">
                        <a href="{{ route('animals.show', $animal) }}" class="font-medium text-slate-900 underline">Ver</a>
                        <a href="{{ route('animals.edit', $animal) }}" class="font-medium text-slate-900 underline">Editar</a>
                        <button
                            type="button"
                            wire:click="deleteAnimal({{ $animal->id }})"
                            wire:confirm="Se eliminara este animal y sus cortes. Continuar?"
                            class="font-medium text-red-700 underline"
                        >Eliminar</button>
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-slate-500">No hay resultados.</p>
            @endforelse
        </div>

        {{-- Desktop table --}}
        <div class="mt-4 hidden overflow-x-auto sm:block">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-slate-600">
                        <th class="py-2">Fecha</th>
                        <th class="py-2">Tipo</th>
                        <th class="py-2">Peso total</th>
                        <th class="py-2">Cortes</th>
                        <th class="py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($animals as $animal)
                        <tr class="border-b">
                            <td class="py-2">{{ $animal->fecha->format('Y-m-d') }}</td>
                            <td class="py-2 capitalize">{{ $animal->animalType->nombre }}</td>
                            <td class="py-2">{{ number_format((float) $animal->peso_total, 3) }} kg</td>
                            <td class="py-2">{{ $animal->cuts->count() }}</td>
                            <td class="py-2">
                                <div class="flex gap-2">
                                    <a href="{{ route('animals.show', $animal) }}" class="text-slate-900 underline">Ver</a>
                                    <a href="{{ route('animals.edit', $animal) }}" class="text-slate-900 underline">Editar</a>
                                    <button
                                        type="button"
                                        wire:click="deleteAnimal({{ $animal->id }})"
                                        wire:confirm="Se eliminara este animal y sus cortes. Continuar?"
                                        class="text-red-700 underline"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-slate-500">No hay resultados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $animals->links() }}</div>
    </div>
</x-layouts.app>
