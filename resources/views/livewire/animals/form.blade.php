<div>
    <h1 class="text-2xl font-semibold">{{ $animal ? 'Editar registro' : 'Nueva entrega' }}</h1>

    @if (session('success'))
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form id="animal-form" wire:submit="save" class="mt-5 space-y-6 pb-20 sm:pb-0">

        {{-- TOP ROW: datos del ingreso + tipificación SENASA --}}
        <div class="grid items-stretch gap-6 lg:grid-cols-2">

            {{-- LEFT: datos del ingreso --}}
            <div class="flex flex-col rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="mb-4 text-base font-semibold">Datos del ingreso</h2>

                {{-- Fila 1: Tipo | Fecha ingreso | Proveedor --}}
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Tipo de animal</label>
                        <select wire:model.live="animal_type_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Seleccionar</option>
                            @foreach($animal_types as $animal_type)
                                <option value="{{ $animal_type->id }}" class="capitalize" @selected((string) $animal_type->id === (string) $animal_type_id)>{{ $animal_type->nombre }}</option>
                            @endforeach
                        </select>
                        @error('animal_type_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Fecha de ingreso</label>
                        <input wire:model="fecha" value="{{ $fecha }}" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('fecha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Proveedor</label>
                        <input wire:model="proveedor" type="text" maxlength="150" placeholder="Nombre del proveedor" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('proveedor') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Fila 2: Frigorífico | Fecha faena --}}
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Frigorífico</label>
                        <input wire:model="frigorifico" type="text" maxlength="150" placeholder="Nombre del frigorífico" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('frigorifico') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Fecha de faena</label>
                        <input wire:model="fecha_faena" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('fecha_faena') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if($this->modalidad === 'media_res')
                    {{-- Fila 3: Peso | Precio | Agregar --}}
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <p class="mb-3 text-xs font-medium uppercase tracking-wide text-slate-500">Agregar res</p>
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <label class="mb-1 block text-sm font-medium">Peso total (kg)</label>
                                <input wire:model="nuevoPeso" type="number" min="0" step="0.001" placeholder="0.000"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @error('nuevoPeso') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex-1">
                                <label class="mb-1 block text-sm font-medium">Precio por kg</label>
                                <input wire:model="nuevoPrecio" type="number" min="0" step="0.01" placeholder="0.00"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @error('nuevoPrecio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            @if(!$animal)
                                <div>
                                    <button type="button" wire:click="addRes"
                                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                                        + Agregar
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if($this->modalidad === 'cajon')
                    <div class="mt-5 border-t border-slate-100 pt-5">
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-600">Detalle de cajones</h3>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium">Cantidad de cajones <span class="text-red-500">*</span></label>
                                <input wire:model.live="cantidad_cajones" type="number" min="0" step="1" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @error('cantidad_cajones') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Aves por caja <span class="text-red-500">*</span></label>
                                <input wire:model="cantidad_aves_por_caja" type="number" min="0" step="1" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @error('cantidad_aves_por_caja') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Peso por caja (kg) <span class="text-red-500">*</span></label>
                                <input wire:model="kg_por_caja" type="number" min="0" step="0.1" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @error('kg_por_caja') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- RIGHT: tipificación SENASA (solo vacunos) --}}
            @if($this->isVacuno)
                <div class="flex flex-col rounded-xl border border-red-100 bg-red-50 p-5">
                    <h2 class="mb-1 text-base font-semibold text-red-800">Tipificación SENASA</h2>
                    <p class="mb-4 text-xs text-red-600">Se aplica a todas las reses de esta entrega.</p>
                    <div class="grid grow grid-cols-3 gap-x-3 gap-y-4 content-start">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Categoría</label>
                            <select wire:model="categoria" class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm">
                                <option value="">Seleccionar</option>
                                <option value="novillo">Novillo</option>
                                <option value="novillito">Novillito</option>
                                <option value="vaquillona">Vaquillona</option>
                                <option value="vaca">Vaca</option>
                                <option value="toro">Toro</option>
                                <option value="buey">Buey</option>
                                <option value="ternero">Ternero</option>
                                <option value="ternera">Ternera</option>
                            </select>
                            @error('categoria') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Dentición</label>
                            <select wire:model="denticion" class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm">
                                <option value="">Seleccionar</option>
                                <option value="0">0 DP</option>
                                <option value="2">2 DP</option>
                                <option value="4">4 DP</option>
                                <option value="6">6 DP</option>
                                <option value="8">8 DP</option>
                            </select>
                            @error('denticion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Conformación</label>
                            <select wire:model="conformacion" class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm">
                                <option value="">Seleccionar</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                            @error('conformacion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Terminación</label>
                            <select wire:model="terminacion" class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm">
                                <option value="">Seleccionar</option>
                                <option value="0">0</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                            @error('terminacion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Color de grasa</label>
                            <select wire:model="color_grasa" class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm">
                                <option value="">Seleccionar</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                            </select>
                            @error('color_grasa') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Color de carne</label>
                            <select wire:model="color_carne" class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm">
                                <option value="">Seleccionar</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                            @error('color_carne') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">pH</label>
                            <input wire:model="ph" type="number" min="4" max="8" step="0.01" class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm">
                            @error('ph') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Temperatura (°C)</label>
                            <input wire:model="temperatura" type="number" min="-10" max="40" step="0.1" class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm">
                            @error('temperatura') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- BOTTOM: lista de reses agregadas --}}
        @if($this->modalidad === 'media_res' && count($reses) > 0)
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="mb-4 text-base font-semibold">Reses de la entrega</h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="pb-2 pr-4 w-10">#</th>
                                <th class="pb-2 pr-4">Peso total (kg)</th>
                                <th class="pb-2 pr-4">Precio por kg</th>
                                <th class="pb-2 pr-4 text-right">Valor</th>
                                @if(!$animal)
                                    <th class="pb-2"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($reses as $index => $res)
                                <tr>
                                    <td class="py-2.5 pr-4 font-medium text-slate-500">{{ $index + 1 }}</td>
                                    <td class="py-2.5 pr-4 font-medium">{{ $res['peso_total'] !== '' ? number_format((float) $res['peso_total'], 3) : '—' }} kg</td>
                                    <td class="py-2.5 pr-4">${{ $res['precio_kg'] !== '' ? number_format((float) $res['precio_kg'], 2) : '—' }}</td>
                                    <td class="py-2.5 pr-4 text-right font-semibold text-slate-800">
                                        ${{ number_format(((float) ($res['peso_total'] !== '' ? $res['peso_total'] : 0)) * ((float) ($res['precio_kg'] !== '' ? $res['precio_kg'] : 0)), 2) }}
                                    </td>
                                    @if(!$animal)
                                        <td class="py-2.5">
                                            <button type="button" wire:click="removeRes({{ $index }})"
                                                class="rounded border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50">Quitar</button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-slate-200 font-semibold text-slate-700">
                                <td class="pt-3 pr-4 text-slate-400">Total</td>
                                <td class="pt-3 pr-4">{{ number_format(collect($reses)->sum(fn($r) => (float) ($r['peso_total'] !== '' ? $r['peso_total'] : 0)), 3) }} kg</td>
                                <td class="pt-3 pr-4 text-xs text-slate-400">{{ count($reses) }} {{ count($reses) === 1 ? 'res' : 'reses' }}</td>
                                <td class="pt-3 pr-4 text-right text-base">${{ number_format($this->valorRes, 2) }}</td>
                                @if(!$animal)<td></td>@endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif

        {{-- Botones de acción --}}
        <div class="flex items-center gap-3">
            <button type="submit" class="hidden rounded-lg bg-slate-900 px-5 py-2 text-sm font-medium text-white hover:bg-slate-700 sm:block">Guardar</button>
            <a href="{{ route('animals.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Cancelar</a>
        </div>

    </form>

    <div class="fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200 bg-white px-4 py-3 sm:hidden">
        <button form="animal-form" type="submit" class="w-full rounded-lg bg-slate-900 py-3 text-sm font-semibold text-white">Guardar</button>
    </div>
</div>
