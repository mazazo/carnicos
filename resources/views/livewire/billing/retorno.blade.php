<div class="mx-auto max-w-xl" @if ($payment->status === 'pending') wire:poll.5s @endif>
    <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        @if ($payment->isPaid())
            <p class="text-4xl">&#10003;</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Pago aprobado</h1>
            <p class="mt-2 text-sm text-slate-600">
                Tu plan {{ $payment->plan?->nombre }} esta activo hasta el {{ $payment->period_end?->format('d/m/Y') }}.
            </p>
            <a href="{{ route($destino) }}" class="mt-6 inline-flex rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-amber-950 hover:bg-amber-400">Ir a mi panel</a>
        @elseif ($payment->status === 'pending')
            <h1 class="text-2xl font-bold text-slate-900">Estamos confirmando tu pago</h1>
            <p class="mt-2 text-sm text-slate-600">Apenas Mercado Pago lo confirme se activa tu plan. Esta pagina se actualiza sola.</p>
        @else
            <h1 class="text-2xl font-bold text-slate-900">El pago no se completo</h1>
            <p class="mt-2 text-sm text-slate-600">Podes intentarlo de nuevo o elegir otro medio de pago.</p>
            <a href="{{ route('billing.plans') }}" class="mt-6 inline-flex rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Volver a planes</a>
        @endif
    </div>
</div>
