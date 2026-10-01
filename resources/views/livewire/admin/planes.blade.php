<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('admin.config.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Configuracion</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Planes</h1>
            <p class="mt-1 text-sm text-slate-600">Lo que se muestra en la pagina de planes: textos, limites y precios por periodo.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('billing.plans') }}" target="_blank" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ver pagina</a>
            <button wire:click="nuevo" class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-bold text-amber-950 hover:bg-amber-400">Nuevo plan</button>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if ($editandoId !== null)
        <form wire:submit="guardar" class="space-y-5 rounded-2xl border border-amber-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">{{ $editandoId ? 'Editar plan' : 'Nuevo plan' }}</h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <label class="text-sm text-slate-700">Nombre
                    <input wire:model="nombre" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    @error('nombre') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm text-slate-700">Codigo
                    <input wire:model="codigo" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    @error('codigo') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm text-slate-700">Orden
                    <input wire:model="orden" type="number" min="0" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
            </div>

            <label class="block text-sm text-slate-700">Descripcion
                <textarea wire:model="descripcion" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                @error('descripcion') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            <div class="grid gap-4 sm:grid-cols-4">
                <label class="text-sm text-slate-700">Tipos de animal (vacio = todos)
                    <input wire:model="max_tipos_animal" type="number" min="1" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    @error('max_tipos_animal') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm text-slate-700">Usuarios (vacio = ilimitados)
                    <input wire:model="max_usuarios" type="number" min="1" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    @error('max_usuarios') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm text-slate-700">Estilo de la card
                    <select wire:model="estilo" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="normal">Normal</option>
                        <option value="destacado">Destacado (mas elegido)</option>
                        <option value="premium">Premium</option>
                    </select>
                </label>
                <div class="space-y-2 pt-6 text-sm text-slate-700">
                    <label class="flex items-center gap-2"><input wire:model="incluye_dashboard" type="checkbox" class="rounded"> Dashboard</label>
                    <label class="flex items-center gap-2"><input wire:model="incluye_app" type="checkbox" class="rounded"> App Android</label>
                    <label class="flex items-center gap-2"><input wire:model="activo" type="checkbox" class="rounded"> Visible / contratable</label>
                </div>
            </div>

            <label class="block text-sm text-slate-700">Caracteristicas extra (una por linea)
                <textarea wire:model="caracteristicas" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
            </label>

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-800">Precios por periodo</p>
                    <button type="button" wire:click="agregarPrecio" class="text-sm font-semibold text-amber-700 hover:underline">+ Agregar periodo</button>
                </div>
                @error('precios') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <div class="space-y-2">
                    @foreach ($precios as $i => $fila)
                        <div wire:key="precio-{{ $i }}" class="flex flex-wrap items-center gap-2">
                            <input wire:model="precios.{{ $i }}.meses" type="number" min="1" max="36" placeholder="Meses" class="w-24 rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <span class="text-sm text-slate-500">meses por $</span>
                            <input wire:model="precios.{{ $i }}.precio" type="number" min="0" step="0.01" placeholder="Precio" class="w-36 rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <label class="flex items-center gap-1 text-sm text-slate-600"><input wire:model="precios.{{ $i }}.activo" type="checkbox" class="rounded"> activo</label>
                            <button type="button" wire:click="quitarPrecio({{ $i }})" class="text-sm text-red-600 hover:underline">Quitar</button>
                            @error("precios.$i.meses") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            @error("precios.$i.precio") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-2">
                <button class="rounded-xl bg-amber-500 px-5 py-2 text-sm font-bold text-amber-950 hover:bg-amber-400">Guardar</button>
                <button type="button" wire:click="cancelar" class="rounded-xl border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
            </div>
        </form>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($planes as $plan)
            <div wire:key="plan-{{ $plan->id }}" class="rounded-2xl border {{ $plan->activo ? 'border-slate-200' : 'border-dashed border-slate-300 opacity-70' }} bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">{{ $plan->codigo }}</p>
                        <h2 class="text-lg font-bold text-slate-900">{{ $plan->nombre }}</h2>
                    </div>
                    @unless ($plan->activo)
                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600">Oculto</span>
                    @endunless
                </div>
                <ul class="mt-3 space-y-1 text-sm text-slate-700">
                    @foreach ($plan->incluye() as $item)
                        <li>&check; {{ $item }}</li>
                    @endforeach
                </ul>
                <ul class="mt-3 space-y-0.5 text-sm text-slate-600">
                    @foreach ($plan->precios as $precio)
                        <li class="{{ $precio->activo ? '' : 'line-through' }}">{{ $precio->meses }} {{ $precio->meses === 1 ? 'mes' : 'meses' }}: ${{ number_format((float) $precio->precio, 0, ',', '.') }}</li>
                    @endforeach
                </ul>
                <button wire:click="editar({{ $plan->id }})" class="mt-4 text-sm font-semibold text-amber-700 hover:underline">Editar</button>
            </div>
        @endforeach
    </div>
</div>
