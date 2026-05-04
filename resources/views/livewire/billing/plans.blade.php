<div>
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-8 flex flex-col gap-4 rounded-3xl border border-stone-200 bg-stone-50 px-6 py-8 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-600">Suscripciones</p>
            <h1 class="mt-3 text-3xl font-bold text-stone-950">Elegi un plan y pasa directo al checkout</h1>
            <p class="mt-2 max-w-2xl text-sm text-stone-500">
                Cada card muestra el valor mensual y te lleva a la nueva vista de pasarela de pago para continuar con la suscripcion.
            </p>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-stone-100 px-5 py-4">
            <p class="text-xs uppercase tracking-[0.2em] text-stone-400">Plan activo</p>
            <p class="mt-1 text-2xl font-bold capitalize text-stone-950">{{ $planActual }}</p>
            <p class="mt-1 text-sm text-stone-500">{{ $user->full_name ?? $user->name }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ($planes as $plan)
            @php
                $esActual = $planActual === $plan['id'];
                $esPago = $plan['precio'] > 0;
                $priceLabel = $plan['precio'] === 0 ? 'Gratis' : '$' . number_format($plan['precio'], 0, ',', '.');
            @endphp

            <div class="relative overflow-hidden rounded-3xl border p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg
                @if ($plan['id'] === 'pro') border-amber-300 bg-amber-50
                @elseif ($plan['id'] === 'enterprise') border-orange-300 bg-orange-50
                @else border-stone-200 bg-stone-50 @endif">

                @if ($plan['id'] === 'pro')
                    <span class="absolute right-4 top-4 rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Mas elegido</span>
                @endif

                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.25em]
                            @if ($plan['id'] === 'pro') text-amber-600
                            @elseif ($plan['id'] === 'enterprise') text-orange-600
                            @else text-stone-400 @endif">
                            {{ $plan['nombre'] }}
                        </p>
                        <h2 class="mt-2 text-4xl font-black text-stone-950">{{ $priceLabel }}</h2>
                        <p class="mt-1 text-sm text-stone-500">
                            @if ($plan['precio'] > 0) por {{ $plan['periodo'] }}
                            @else sin costo mensual
                            @endif
                        </p>
                    </div>
                    @if ($esActual)
                        <span class="rounded-full border border-stone-300 bg-stone-200 px-3 py-1 text-xs font-semibold text-stone-700">Actual</span>
                    @endif
                </div>

                <p class="mt-4 text-sm leading-relaxed text-stone-600">{{ $plan['descripcion'] }}</p>

                <ul class="mt-5 space-y-2">
                    @foreach (array_slice($plan['features'], 0, 4) as $feature)
                        <li class="flex items-start gap-2 text-sm text-stone-700">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full
                                @if ($plan['id'] === 'pro') bg-amber-500
                                @elseif ($plan['id'] === 'enterprise') bg-orange-500
                                @else bg-stone-400 @endif">
                            </span>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6 border-t pt-5
                    @if ($plan['id'] === 'pro') border-amber-200
                    @elseif ($plan['id'] === 'enterprise') border-orange-200
                    @else border-stone-200 @endif">
                    @if ($esActual)
                        <div class="inline-flex w-full items-center justify-center rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm font-semibold text-stone-500">
                            Plan actual
                        </div>
                    @elseif ($esPago)
                        <a href="{{ route('billing.checkout', ['plan' => $plan['id']]) }}"
                           class="inline-flex w-full items-center justify-center rounded-2xl px-4 py-3 text-sm font-bold transition
                               @if ($plan['id'] === 'pro') bg-amber-500 text-amber-950 hover:bg-amber-400
                               @elseif ($plan['id'] === 'enterprise') bg-orange-500 text-orange-950 hover:bg-orange-400
                               @else bg-stone-800 text-stone-100 hover:bg-stone-700 @endif">
                            Suscribirse &rarr;
                        </a>
                    @else
                        <div class="inline-flex w-full items-center justify-center rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm font-semibold text-stone-500">
                            Plan gratuito
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
