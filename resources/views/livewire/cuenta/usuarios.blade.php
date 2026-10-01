@php
    $campo = 'w-full rounded-lg border border-stone-300 px-3 py-2 text-sm';
    $etiqueta = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500';
    $ayuda = [
        'ingresos' => 'Cargar y ver ingresos de medias, cajones y piezas.',
        'producciones' => 'Despostar, cuartear y ver las producciones.',
        'cortes' => 'Habilitar cortes y cargar los propios.',
        'app' => 'Entrar con su usuario en la app del celular.',
    ];
@endphp
<div class="mx-auto max-w-4xl">
    <x-ui.page-header title="Usuarios" :subtitle="'Plan '.($plan?->nombre ?? '-').': '.($plan?->max_usuarios === null ? 'usuarios ilimitados' : 'hasta '.$plan?->max_usuarios.' '.($plan?->max_usuarios === 1 ? 'usuario' : 'usuarios')).' · usás '.$usuarios->count().'.'">
        @if ($esDueno)
            @if ($puedeCrear)
                <x-ui.button variant="accent" icon="plus" wire:click="nuevo">Nuevo usuario</x-ui.button>
            @else
                <x-ui.button variant="secondary" :href="route('billing.plans')">Tu plan no permite más usuarios · ver planes</x-ui.button>
            @endif
        @endif
    </x-ui.page-header>

    @if (session('success'))
        <x-ui.alert class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif

    {{-- Nuevo usuario con sus permisos --}}
    @if ($mostrarFormulario)
        <x-ui.card title="Nuevo usuario" subtitle="Se crea como empleado. Elegí qué puede usar." class="mb-6">
            <form wire:submit="crear" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach (['name' => 'Nombre', 'last_name' => 'Apellido (opcional)', 'email' => 'Email', 'movil' => 'Móvil', 'password' => 'Contraseña'] as $nombreCampo => $texto)
                        <div wire:key="campo-{{ $nombreCampo }}">
                            <label class="{{ $etiqueta }}" for="campo-{{ $nombreCampo }}">{{ $texto }}</label>
                            <input id="campo-{{ $nombreCampo }}" wire:model="{{ $nombreCampo }}" type="{{ $nombreCampo === 'password' ? 'password' : ($nombreCampo === 'email' ? 'email' : 'text') }}" class="{{ $campo }}">
                            @error($nombreCampo)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>

                <fieldset>
                    <legend class="{{ $etiqueta }}">Permisos</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($listaPermisos as $clave => $nombre)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-stone-200 p-3 hover:border-amber-400 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50">
                                <input type="checkbox" wire:model="permisos" value="{{ $clave }}" class="mt-0.5 rounded border-stone-300 text-amber-500 focus:ring-amber-500">
                                <span>
                                    <span class="block text-sm font-semibold text-stone-900">{{ $nombre }}</span>
                                    <span class="block text-xs text-stone-500">{{ $ayuda[$clave] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-stone-500">Usuarios y Planes son solo del dueño.</p>
                </fieldset>

                <div class="flex justify-end gap-2 border-t border-stone-100 pt-4">
                    <x-ui.button variant="ghost" wire:click="$set('mostrarFormulario', false)">Cancelar</x-ui.button>
                    <x-ui.button type="submit" variant="primary" icon="check">Crear usuario</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    {{-- Listado --}}
    <x-ui.card :padding="false">
        <ul class="divide-y divide-stone-100">
            @foreach ($usuarios as $usuario)
                <li wire:key="usuario-{{ $usuario->id }}" class="px-5 py-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-stone-100 text-xs font-bold text-stone-600">
                            {{ collect(explode(' ', trim($usuario->full_name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-stone-900">{{ $usuario->full_name }}</p>
                            <p class="truncate text-sm text-stone-500">{{ $usuario->email }}</p>
                        </div>
                        @if ($usuario->esDueno())
                            <x-ui.badge tone="amber">Dueño · todos los permisos</x-ui.badge>
                        @else
                            <div class="flex flex-wrap gap-1">
                                @forelse ($usuario->permisos ?? [] as $p)
                                    <x-ui.badge tone="stone">{{ $listaPermisos[$p] ?? $p }}</x-ui.badge>
                                @empty
                                    <x-ui.badge tone="red">Sin permisos</x-ui.badge>
                                @endforelse
                            </div>
                            @if ($esDueno && $editandoId !== $usuario->id)
                                <x-ui.button size="sm" variant="secondary" icon="pencil" wire:click="editarPermisos({{ $usuario->id }})">Permisos</x-ui.button>
                                <button type="button" wire:click="eliminar({{ $usuario->id }})" wire:confirm="¿Eliminar a {{ $usuario->email }}?" class="rounded-lg p-2 text-stone-400 hover:bg-red-50 hover:text-red-700" title="Eliminar" aria-label="Eliminar a {{ $usuario->email }}">
                                    <x-ui.icon name="trash" class="h-4 w-4" />
                                </button>
                            @endif
                        @endif
                    </div>

                    @if ($editandoId === $usuario->id)
                        <form wire:submit="guardarPermisos" class="mt-4 rounded-xl bg-stone-50 p-4">
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($listaPermisos as $clave => $nombre)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-stone-200 bg-white px-3 py-2 has-[:checked]:border-amber-500">
                                        <input type="checkbox" wire:model="permisosEditando" value="{{ $clave }}" class="rounded border-stone-300 text-amber-500 focus:ring-amber-500">
                                        <span class="text-sm font-medium text-stone-800">{{ $nombre }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="mt-3 flex justify-end gap-2">
                                <x-ui.button size="sm" variant="ghost" wire:click="cancelarPermisos">Cancelar</x-ui.button>
                                <x-ui.button size="sm" type="submit" variant="primary">Guardar permisos</x-ui.button>
                            </div>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-ui.card>
</div>
