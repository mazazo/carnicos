@props(['title' => null, 'subtitle' => null, 'padding' => true])
{{-- Tarjeta blanca. Slot "actions" opcional a la derecha del título. --}}
<section {{ $attributes->merge(['class' => 'rounded-2xl border border-stone-200 bg-white shadow-sm']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
            <div>
                @if ($title)<h2 class="text-base font-semibold text-stone-900">{{ $title }}</h2>@endif
                @if ($subtitle)<p class="mt-0.5 text-sm text-stone-500">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif
    <div @class(['p-5' => $padding])>
        {{ $slot }}
    </div>
</section>
