<div>
    <h1 class="text-2xl font-semibold">Dashboard</h1>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-600">Reses cargadas</p>
            <p class="mt-1 text-3xl font-bold">{{ $reses_count }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-600">Cajones cargados</p>
            <p class="mt-1 text-3xl font-bold">{{ $cajones_count }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-600">Producciones esta semana</p>
            <p class="mt-1 text-3xl font-bold">{{ $produccion_semana }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <a href="{{ route('animals.create', ['tipo' => 'vacuno']) }}" class="rounded-lg border-2 border-red-600 bg-red-50 px-4 py-3 text-sm font-semibold text-black hover:bg-red-100">
            Ingresar res vacuna
        </a>
        <a href="{{ route('animals.create', ['tipo' => 'porcino']) }}" class="rounded-lg border-2 border-rose-600 bg-rose-50 px-4 py-3 text-sm font-semibold text-black hover:bg-rose-100">
            Ingresar res de cerdo
        </a>
        <a href="{{ route('animals.create', ['tipo' => 'avicola']) }}" class="rounded-lg border-2 border-yellow-600 bg-yellow-50 px-4 py-3 text-sm font-semibold text-black hover:bg-yellow-100">
            Ingresar cajones de pollo
        </a>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="rounded-xl border-2 border-red-300 bg-white p-5">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-red-700">Tabla de reses vacunas</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-red-200 text-left text-slate-600">
                            <th class="py-2">Fecha</th>
                            <th class="py-2">Tipo</th>
                            <th class="py-2">Peso total</th>
                            <th class="py-2">Cortes</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vacuno_animals as $animal)
                            <tr class="border-b">
                                <td class="py-2">{{ $animal->fecha->format('Y-m-d') }}</td>
                                <td class="py-2 capitalize">{{ $animal->animalType->nombre }}</td>
                                <td class="py-2">{{ number_format((float) $animal->peso_total, 3) }} kg</td>
                                <td class="py-2">{{ $animal->cuts_count }}</td>
                                <td class="py-2"><a href="{{ route('animals.show', $animal) }}" class="text-red-600 underline">Ver</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-slate-500">Sin reses vacunas cargadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border-2 border-rose-300 bg-white p-5">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-rose-700">Tabla de reses de cerdo</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-rose-200 text-left text-slate-600">
                            <th class="py-2">Fecha</th>
                            <th class="py-2">Tipo</th>
                            <th class="py-2">Peso total</th>
                            <th class="py-2">Cortes</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($porcino_animals as $animal)
                            <tr class="border-b">
                                <td class="py-2">{{ $animal->fecha->format('Y-m-d') }}</td>
                                <td class="py-2 capitalize">{{ $animal->animalType->nombre }}</td>
                                <td class="py-2">{{ number_format((float) $animal->peso_total, 3) }} kg</td>
                                <td class="py-2">{{ $animal->cuts_count }}</td>
                                <td class="py-2"><a href="{{ route('animals.show', $animal) }}" class="text-rose-600 underline">Ver</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-slate-500">Sin reses de cerdo cargadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border-2 border-yellow-300 bg-white p-5">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-yellow-700">Tabla de cajones de pollo</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-yellow-200 text-left text-slate-600">
                            <th class="py-2">Fecha</th>
                            <th class="py-2">Tipo</th>
                            <th class="py-2">Cantidad</th>
                            <th class="py-2">Cortes</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($avicola_animals as $animal)
                            <tr class="border-b">
                                <td class="py-2">{{ $animal->fecha->format('Y-m-d') }}</td>
                                <td class="py-2 capitalize">{{ $animal->animalType->nombre }}</td>
                                <td class="py-2">{{ number_format((float) $animal->peso_total, 3) }}</td>
                                <td class="py-2">{{ $animal->cuts_count }}</td>
                                <td class="py-2"><a href="{{ route('animals.show', $animal) }}" class="text-yellow-600 underline">Ver</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-slate-500">Sin cajones de pollo cargados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
