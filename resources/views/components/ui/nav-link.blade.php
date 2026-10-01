@props(['href', 'icon', 'active' => false])
{{-- Ítem de la barra lateral. --}}
<a href="{{ $href }}" @class([
    'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
    'bg-amber-500/15 text-amber-300' => $active,
    'text-stone-300 hover:bg-white/5 hover:text-white' => ! $active,
])>
    <x-ui.icon :name="$icon" @class(['h-5 w-5', 'text-amber-400' => $active, 'text-stone-500 group-hover:text-stone-300' => ! $active]) />
    <span class="truncate">{{ $slot }}</span>
</a>
