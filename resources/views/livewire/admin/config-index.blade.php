<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-700">Configuracion admin</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Panel de configuracion</h1>
            <p class="mt-1 text-sm text-slate-600">Accesos rapidos para gestionar carnicerias, planes, pagos, cortes y logs.</p>
        </div>
        <a href="{{ route('dashboard.pro') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Volver al dashboard
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <a href="{{ route('admin.carnicerias.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Clientes</p>
            <h2 class="mt-2 text-lg font-bold text-slate-900">Carnicerias</h2>
            <p class="mt-2 text-sm text-slate-600">Estado de prueba o plan, vencimientos, sumar dias, habilitar planes y suspender.</p>
            <span class="mt-4 inline-flex text-xs font-semibold text-amber-700">Abrir modulo &rarr;</span>
        </a>

        <a href="{{ route('admin.pagos') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Cobros</p>
            <h2 class="mt-2 text-lg font-bold text-slate-900">Pagos @if ($pagosPendientes) <span class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">{{ $pagosPendientes }} pendiente(s)</span>@endif</h2>
            <p class="mt-2 text-sm text-slate-600">Aprobar o rechazar avisos de transferencia y ver los pagos de Mercado Pago.</p>
            <span class="mt-4 inline-flex text-xs font-semibold text-amber-700">Abrir modulo &rarr;</span>
        </a>

        <a href="{{ route('admin.planes') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Catalogo</p>
            <h2 class="mt-2 text-lg font-bold text-slate-900">Planes y precios</h2>
            <p class="mt-2 text-sm text-slate-600">Editar lo que muestra la pagina de planes: limites, caracteristicas y precios por periodo.</p>
            <span class="mt-4 inline-flex text-xs font-semibold text-amber-700">Abrir modulo &rarr;</span>
        </a>

        <a href="{{ route('admin.config.cortes') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Auditoria</p>
            <h2 class="mt-2 text-lg font-bold text-slate-900">Cortes por tipo de animal</h2>
            <p class="mt-2 text-sm text-slate-600">Revisar asociacion de cortes por tipo y detectar inconsistencias de datos.</p>
            <span class="mt-4 inline-flex text-xs font-semibold text-amber-700">Abrir modulo &rarr;</span>
        </a>

        <a href="{{ route('admin.config.logs') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Soporte</p>
            <h2 class="mt-2 text-lg font-bold text-slate-900">Logs de la aplicacion</h2>
            <p class="mt-2 text-sm text-slate-600">Ver los errores que registra el sistema, con su detalle, para diagnosticar fallas.</p>
            <span class="mt-4 inline-flex text-xs font-semibold text-amber-700">Abrir modulo &rarr;</span>
        </a>
    </div>
</div>
