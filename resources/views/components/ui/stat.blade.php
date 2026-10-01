@props(['label', 'value', 'emoji' => null, 'hint' => null, 'href' => null, 'compacto' => false])
{{-- Indicador numérico del resumen. Con href es una tarjeta clickeable; "compacto" la achica (dashboard). --}}
@php
    $clases = ($compacto
        ? 'flex items-center gap-3 rounded-xl border border-stone-200 bg-white px-3 py-2.5 shadow-sm'
        : 'flex items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm sm:gap-4')
        .($href ? ' group transition hover:border-amber-400 hover:shadow-md' : '');
@endphp
@if ($href)<a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>@else<div {{ $attributes->merge(['class' => $clases]) }}>@endif
    @if ($emoji)
        <div @class([
            'flex shrink-0 items-center justify-center rounded-full bg-amber-100',
            'h-8 w-8 text-base' => $compacto,
            'h-9 w-9 text-lg sm:h-11 sm:w-11 sm:text-xl' => ! $compacto,
        ])>{{ $emoji }}</div>
    @endif
    <div class="min-w-0 flex-1">
        <p class="text-[11px] font-medium uppercase leading-tight tracking-wide text-stone-500 {{ $compacto ? 'line-clamp-2' : 'sm:text-xs' }}">{{ $label }}</p>
        <p @class(['font-bold tabular-nums leading-tight text-stone-900', 'text-lg' => $compacto, 'text-xl sm:text-2xl' => ! $compacto])>{{ $value }}</p>
        @if ($hint)<p @class(['text-xs leading-tight', 'font-semibold text-amber-700' => $href, 'text-stone-400' => ! $href])>{{ $hint }}</p>@endif
    </div>
@if ($href)</a>@else</div>@endif
