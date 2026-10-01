@props(['tone' => 'stone'])
{{-- Etiqueta chica. tone: stone | amber | green | red --}}
@php
    $tonos = [
        'stone' => 'bg-stone-100 text-stone-700 ring-stone-200',
        'amber' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'red' => 'bg-red-50 text-red-700 ring-red-200',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset '.($tonos[$tone] ?? $tonos['stone'])]) }}>{{ $slot }}</span>
