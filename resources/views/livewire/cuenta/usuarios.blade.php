<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Usuarios de la carniceria</h1>
            <p class="text-sm text-slate-600">
                Plan {{ $plan?->nombre ?? '-' }}:
                {{ $plan?->max_usuarios === null ? 'usuarios ilimitados' : ('hasta '.$plan?->max_usuarios.' '.($plan?->max_usuarios === 1 ? 'usuario' : 'usuarios')) }}
                &middot; usas {{ $usuarios->count() }}.
            </p>
        </div>
        @if ($esDueno)
            @if ($puedeCrear)
                <button wire:click="nuevo" class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-bold text-amber-950 hover:bg-amber-400">+ Nuevo usuario</button>
            @else
                <a href="{{ route('billing.plans') }}" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800">Tu plan no permite mas usuarios &middot; ver planes</a>
            @endif
        @endif
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if ($mostrarFormulario)
        <form wire:submit="crear" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2">
            @foreach (['name' => 'Nombre', 'last_name' => 'Apellido (opcional)', 'email' => 'Email', 'movil' => 'Movil', 'password' => 'Contrasena'] as $campo => $etiqueta)
                <div wire:key="campo-{{ $campo }}">
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ $etiqueta }}</label>
                    <input wire:model="{{ $campo }}" type="{{ $campo === 'password' ? 'password' : ($campo === 'email' ? 'email' : 'text') }}"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                    @error($campo) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <div class="flex items-end gap-2">
                <button class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-bold text-amber-950 hover:bg-amber-400">Crear usuario</button>
                <button type="button" wire:click="$set('mostrarFormulario', false)" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Cancelar</button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                <tr><th class="px-4 py-3">Usuario</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Rol</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($usuarios as $usuario)
                    <tr wire:key="usuario-{{ $usuario->id }}">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $usuario->full_name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $usuario->email }}</td>
                        <td class="px-4 py-3">{{ $usuario->esDueno() ? 'Dueno' : 'Empleado' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($esDueno && ! $usuario->esDueno())
                                <button wire:click="eliminar({{ $usuario->id }})" wire:confirm="¿Eliminar a {{ $usuario->email }}?" class="text-xs font-semibold text-red-600 hover:underline">Eliminar</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
