@props(['tone' => 'green'])
{{-- Mensaje de estado (avisos, session flash). tone: green | red | amber --}}
@php
    $tonos = [
        'green' => ['border-emerald-200 bg-emerald-50 text-emerald-800', 'check'],
        'red' => ['border-red-200 bg-red-50 text-red-800', 'warning'],
        'amber' => ['border-amber-200 bg-amber-50 text-amber-900', 'warning'],
    ];
    [$clases, $icono] = $tonos[$tone] ?? $tonos['green'];
@endphp
<div {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-xl border px-4 py-3 text-sm '.$clases]) }}>
    <x-ui.icon :name="$icono" class="mt-0.5 h-4 w-4" />
    <div>{{ $slot }}</div>
</div>
