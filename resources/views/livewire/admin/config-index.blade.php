<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-700">Configuracion admin</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Panel de configuracion</h1>
            <p class="mt-1 text-sm text-slate-600">Accesos rapidos para gestionar usuarios, estados de pago y validar cortes por tipo.</p>
        </div>
        <a href="{{ route('dashboard.pro') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Volver al dashboard
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <a href="{{ route('admin.config.usuarios') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Gestion</p>
            <h2 class="mt-2 text-lg font-bold text-slate-900">Usuarios y estado de abono</h2>
            <p class="mt-2 text-sm text-slate-600">Ver usuarios, rol, estado, si estan abonando y vencimiento.</p>
            <span class="mt-4 inline-flex text-xs font-semibold text-amber-700">Abrir modulo &rarr;</span>
        </a>

        <a href="{{ route('admin.config.cortes') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Auditoria</p>
            <h2 class="mt-2 text-lg font-bold text-slate-900">Cortes por tipo de animal</h2>
            <p class="mt-2 text-sm text-slate-600">Revisar asociacion de cortes por tipo y detectar inconsistencias de datos.</p>
            <span class="mt-4 inline-flex text-xs font-semibold text-amber-700">Abrir modulo &rarr;</span>
        </a>
    </div>
</div>
