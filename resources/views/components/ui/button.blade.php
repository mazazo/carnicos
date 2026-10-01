@props(['variant' => 'primary', 'href' => null, 'icon' => null, 'size' => 'md'])
{{-- Botón, o enlace con estilo de botón si tiene href. variant: primary | accent | secondary | danger | ghost --}}
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/50 disabled:cursor-not-allowed disabled:opacity-50';
    $tamanos = ['sm' => 'px-3 py-1.5 text-xs', 'md' => 'px-4 py-2 text-sm'];
    $variantes = [
        'primary' => 'bg-stone-900 text-white shadow-sm hover:bg-stone-800',
        'accent' => 'bg-amber-500 text-stone-950 shadow-sm hover:bg-amber-400',
        'secondary' => 'border border-stone-300 bg-white text-stone-700 shadow-sm hover:bg-stone-50',
        'danger' => 'border border-red-200 bg-white text-red-700 hover:bg-red-50',
        'ghost' => 'text-stone-600 hover:bg-stone-100 hover:text-stone-900',
    ];
    $clases = $base.' '.($tamanos[$size] ?? $tamanos['md']).' '.($variantes[$variant] ?? $variantes['primary']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>
        @if ($icon)<x-ui.icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $clases]) }}>
        @if ($icon)<x-ui.icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
    </button>
@endif
