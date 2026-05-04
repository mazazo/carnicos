<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-700">Configuracion admin</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Usuarios y estado de suscripcion</h1>
            <p class="mt-1 text-sm text-slate-600">Control de acceso, estado actual de abono y fecha de vencimiento.</p>
        </div>
        <a href="{{ route('dashboard.pro') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Volver al dashboard
        </a>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-slate-700">
                <tr>
                    <th class="px-4 py-3 font-semibold">Usuario</th>
                    <th class="px-4 py-3 font-semibold">Email</th>
                    <th class="px-4 py-3 font-semibold">Rol</th>
                    <th class="px-4 py-3 font-semibold">Plan</th>
                    <th class="px-4 py-3 font-semibold">Estado</th>
                    <th class="px-4 py-3 font-semibold">Abonando</th>
                    <th class="px-4 py-3 font-semibold">Vencimiento</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $subscription = $user->subscription;
                        $status = $subscription?->status ?? 'sin-suscripcion';
                        $isPaying = $subscription !== null && in_array($status, ['active', 'trial'], true);
                        $endsAt = $subscription?->ends_at ?? $subscription?->trial_ends_at;
                    @endphp
                    <tr class="border-t border-slate-200">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $user->full_name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->isAdmin() ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                {{ $user->isAdmin() ? 'Admin' : 'Usuario' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 capitalize text-slate-700">{{ $user->plan }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                {{ $status === 'active' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                {{ $status === 'trial' ? 'bg-sky-100 text-sky-800' : '' }}
                                {{ !in_array($status, ['active', 'trial'], true) ? 'bg-slate-100 text-slate-700' : '' }}">
                                {{ $status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $isPaying ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-700' }}">
                                {{ $isPaying ? 'Si' : 'No' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            {{ $endsAt ? $endsAt->format('Y-m-d') : 'Sin fecha' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">No hay usuarios cargados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
