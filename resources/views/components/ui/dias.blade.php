@props(['animal'])
{{--
    Hace cuántos días ingresó una media, cajón o pieza (solo si sigue en stock).
    Verde hasta 2 días, ámbar de 3 a 5, rojo desde 6.
--}}
@if ($animal->enStock())
    @php
        $dias = $animal->diasEnStock();
        $tono = $dias <= 2 ? 'green' : ($dias <= 5 ? 'amber' : 'red');
        $texto = $dias === 0 ? 'Hoy' : ($dias === 1 ? '1 día' : $dias.' días');
    @endphp
    <x-ui.badge :tone="$tono" {{ $attributes }} title="Ingresó el {{ $animal->fecha?->format('d/m/Y') }}">
        <x-ui.icon name="clock" class="h-3 w-3" />{{ $texto }}
    </x-ui.badge>
@endif
