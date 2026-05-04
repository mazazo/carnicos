<div>
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ session('success') }}
        </div>
    @endif

    @php
        $metodos = [
            'mercadopago' => ['nombre' => 'Mercado Pago', 'detalle' => 'Checkout local con tarjetas, saldo y QR.'],
            'stripe' => ['nombre' => 'Stripe', 'detalle' => 'Tarjetas internacionales y billing recurrente.'],
            'transferencia' => ['nombre' => 'Transferencia', 'detalle' => 'Validacion manual para cuentas corporativas.'],
        ];
    @endphp

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('billing.plans') }}" class="inline-flex items-center gap-1 text-sm font-medium text-stone-500 hover:text-stone-800">
                &larr; Volver a planes
            </a>
            <h1 class="mt-2 text-3xl font-bold text-stone-950">Pasarela de pago</h1>
            <p class="mt-1 text-sm text-stone-500">Revisa el plan, elegi un metodo y deja listo el siguiente paso de integracion.</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-600">Plan elegido</p>
            <p class="mt-1 text-2xl font-black text-stone-950">{{ $plan['nombre'] }}</p>
            <p class="mt-1 text-sm text-stone-500">${{ number_format($plan['precio'], 0, ',', '.') }}/{{ $plan['periodo'] }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="space-y-6">
            <div class="rounded-3xl border border-stone-200 bg-stone-50 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-stone-400">Resumen</p>
                        <h2 class="mt-2 text-2xl font-bold text-stone-950">{{ $plan['nombre'] }}</h2>
                        <p class="mt-2 text-sm text-stone-500">{{ $plan['descripcion'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-amber-200 bg-amber-100 px-4 py-3 text-right">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-600">Total</p>
                        <p class="mt-1 text-2xl font-black text-stone-950">${{ number_format($plan['precio'], 0, ',', '.') }}</p>
                        <p class="text-xs text-stone-500">por {{ $plan['periodo'] }}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-2 sm:grid-cols-2">
                    @foreach ($plan['features'] as $feature)
                        <div class="flex items-start gap-2 rounded-xl border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-700">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"></span>
                            {{ $feature }}
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-stone-50 p-6">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-stone-400">Metodo de pago</p>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    @foreach ($metodos as $id => $metodo)
                        <button
                            type="button"
                            wire:click="seleccionarMetodo('{{ $id }}')"
                            class="rounded-2xl border p-4 text-left transition
                                @if ($this->metodo === $id) border-amber-400 bg-amber-50 shadow-sm
                                @else border-stone-200 bg-stone-100 hover:border-stone-300 hover:bg-stone-200 @endif"
                        >
                            <p class="text-sm font-bold @if ($this->metodo === $id) text-amber-800 @else text-stone-700 @endif">
                                {{ $metodo['nombre'] }}
                            </p>
                            <p class="mt-1 text-xs @if ($this->metodo === $id) text-amber-700 @else text-stone-500 @endif">
                                {{ $metodo['detalle'] }}
                            </p>
                        </button>
                    @endforeach
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button wire:click="continuar" class="rounded-2xl bg-amber-500 px-5 py-3 text-sm font-bold text-amber-950 transition hover:bg-amber-400">
                        Continuar con {{ $metodos[$this->metodo]['nombre'] }} &rarr;
                    </button>
                    <span class="text-sm text-stone-500">La integracion queda lista para conectar el checkout real.</span>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-stone-200 bg-stone-50 p-6">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-stone-400">Facturacion</p>
                <div class="mt-4 divide-y divide-stone-200">
                    <div class="py-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-stone-400">Titular</p>
                        <p class="mt-1 font-semibold text-stone-900">{{ $user->full_name ?? $user->name }}</p>
                    </div>
                    <div class="py-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-stone-400">Email</p>
                        <p class="mt-1 font-semibold text-stone-900">{{ $user->email }}</p>
                    </div>
                    <div class="py-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-stone-400">Cobro</p>
                        <p class="mt-1 font-semibold text-stone-900">Mensual</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-dashed border-stone-300 bg-stone-100 p-6">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-stone-400">Siguiente paso</p>
                <p class="mt-3 text-sm leading-6 text-stone-600">
                    Cuando definas la integracion real, este punto puede crear la preferencia de pago, la sesion de checkout o la orden bancaria sin tocar la pantalla de planes.
                </p>
            </div>
        </div>
    </div>
</div>

    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Resumen</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">{{ $plan['nombre'] }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ $plan['descripcion'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-right">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Total</p>
                        <p class="mt-1 text-2xl font-black text-slate-950">${{ number_format($plan['precio'], 0, ',', '.') }}</p>
                        <p class="text-xs text-slate-500">por {{ $plan['periodo'] }}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach ($plan['features'] as $feature)
                        <div class="flex items-start gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span>{{ $feature }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Metodo de pago</p>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach ($metodos as $id => $metodo)
                        <button
                            type="button"
                            wire:click="seleccionarMetodo('{{ $id }}')"
                            class="rounded-2xl border p-4 text-left transition
                                @if ($this->metodo === $id) border-emerald-500 bg-emerald-50 text-emerald-900 shadow-lg
                                @else border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-400 hover:bg-white @endif"
                        >
                            <p class="text-sm font-semibold">{{ $metodo['nombre'] }}</p>
                            <p class="mt-2 text-sm @if ($this->metodo === $id) text-emerald-700 @else text-slate-500 @endif">{{ $metodo['detalle'] }}</p>
                        </button>
                    @endforeach
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <button wire:click="continuar" class="rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-emerald-950 transition hover:bg-emerald-400">
                        Continuar con {{ $metodos[$this->metodo]['nombre'] }}
                    </button>
                    <span class="text-sm text-slate-500">La integracion final queda preparada para conectar el checkout real.</span>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Facturacion</p>
                <div class="mt-4 space-y-4 text-sm text-slate-600">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Titular</p>
                        <p class="mt-1 font-medium text-slate-900">{{ $user->full_name ?? $user->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Email</p>
                        <p class="mt-1 font-medium text-slate-900">{{ $user->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Cobro</p>
                        <p class="mt-1 font-medium text-slate-900">Mensual</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Siguiente paso</p>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Cuando definas la integracion real, este punto puede crear la preferencia de pago, la sesion de checkout o la orden bancaria sin tocar la pantalla de planes.
                </p>
            </div>
        </div>
    </div>
</div>