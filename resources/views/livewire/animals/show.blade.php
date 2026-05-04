<x-layouts.app>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Detalle de animal</h1>
        <div class="flex gap-2">
            <a href="{{ route('animals.edit', $animal) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Editar</a>
            <a href="{{ route('animals.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Volver</a>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-semibold">Datos del animal</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 text-sm">
                <div><span class="text-slate-600">Tipo:</span> <span class="font-medium capitalize">{{ $animal->animalType->nombre }}</span></div>
                <div><span class="text-slate-600">Fecha:</span> <span class="font-medium">{{ $animal->fecha->format('Y-m-d') }}</span></div>
                <div><span class="text-slate-600">Peso total:</span> <span class="font-medium">{{ number_format((float) $animal->peso_total, 3) }} kg</span></div>
                <div><span class="text-slate-600">Precio kg animal:</span> <span class="font-medium">${{ number_format((float) ($animal->precio_kg ?? 0), 2) }}</span></div>
            </div>

            <h3 class="mt-6 text-base font-semibold">Cortes</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-slate-600">
                            <th class="py-2">Nombre</th>
                            <th class="py-2">Peso</th>
                            <th class="py-2">Precio kg</th>
                            <th class="py-2">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($animal->cuts as $cut)
                            <tr class="border-b">
                                <td class="py-2">{{ $cut->nombre }}</td>
                                <td class="py-2">{{ number_format((float) $cut->peso, 3) }} kg</td>
                                <td class="py-2">${{ number_format((float) ($cut->precio_kg ?? 0), 2) }}</td>
                                <td class="py-2">${{ number_format((float) $cut->peso * (float) ($cut->precio_kg ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-semibold">Resumen</h2>
            <div class="mt-3 space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Rendimiento (kg)</span>
                    <span class="font-semibold">{{ number_format($this->rendimientoKg, 3) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Rendimiento (%)</span>
                    <span class="font-semibold">{{ number_format($this->rendimientoPorcentaje, 2) }}%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Valor total</span>
                    <span class="font-semibold">${{ number_format($this->valorTotal, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
