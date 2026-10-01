@props(['emoji' => '🔪'])
{{-- Estado vacío de listas y tablas. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-stone-300 px-4 py-8 text-center']) }}>
    <span class="text-2xl">{{ $emoji }}</span>
    <p class="text-sm text-stone-500">{{ $slot }}</p>
</div>
