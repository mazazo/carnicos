@php
    $plata = fn ($v) => ((float) $v < 0 ? '-' : '').'$ '.number_format(abs((float) $v), 0, ',', '.');
    $kg = fn ($v) => number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 2, ',', '.').' kg';
    $pct = fn ($v) => $v === null ? '-' : number_format((float) $v, 1, ',', '.').'%';
    $margen = $r['costo_total'] > 0 ? $r['diferencia'] / $r['costo_total'] * 100 : null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Desposte {{ $r['tipo'] }} #{{ $r['id'] }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1c1917; }
        .ambar { color: #b45309; }
        .gris { color: #78716c; }
        h1 { font-size: 18px; margin: 0; }
        .encabezado { width: 100%; border-bottom: 2px solid #f59e0b; padding-bottom: 8px; margin-bottom: 14px; }
        .encabezado td { vertical-align: top; }
        .datos { width: 100%; border-collapse: separate; border-spacing: 6px 6px; margin: 0 -6px 10px; }
        .datos td { width: 25%; border: 1px solid #e7e5e4; border-radius: 6px; padding: 7px 9px; }
        .etiqueta { font-size: 8.5px; text-transform: uppercase; letter-spacing: .5px; color: #78716c; }
        .valor { font-size: 14px; font-weight: bold; margin-top: 2px; }
        .verde { color: #047857; }
        .rojo { color: #b91c1c; }
        h2 { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #b45309; margin: 14px 0 5px; }
        table.tabla { width: 100%; border-collapse: collapse; }
        .tabla th { background: #b45309; color: #fff; text-align: left; padding: 5px 7px; font-size: 9px; text-transform: uppercase; }
        .tabla td { padding: 5px 7px; border-bottom: 1px solid #f5f5f4; }
        .tabla tr:nth-child(even) td { background: #fafaf9; }
        .tabla th.num, .tabla td.num { text-align: right; }
        .total td { font-weight: bold; border-top: 2px solid #1c1917; background: #fff !important; }
        .pie { margin-top: 18px; font-size: 8.5px; color: #a8a29e; }
    </style>
</head>
<body>
    <table class="encabezado">
        <tr>
            <td>
                <h1>{{ $carniceria }}</h1>
                <div class="gris">{{ ($r['modo'] ?? 'musculo') === 'cuarteo' ? 'Cuarteo' : 'Resumen de desposte' }} · {{ $r['tipo'] }}{{ ($r['modo'] ?? '') === 'primario' ? ' · por piezas grandes' : '' }}</div>
            </td>
            <td style="text-align: right">
                <div style="font-weight: bold; font-size: 13px">#{{ $r['id'] }}</div>
                <div>{{ \Illuminate\Support\Carbon::parse($r['fecha'])->format('d/m/Y') }}</div>
                @if ($r['despostador'])<div class="gris">{{ $r['despostador'] }}</div>@endif
            </td>
        </tr>
    </table>

    <table class="datos">
        <tr>
            <td><div class="etiqueta">Kg medias</div><div class="valor">{{ $kg($r['peso_medias']) }}</div></td>
            <td><div class="etiqueta">Kg cortes</div><div class="valor">{{ $kg($r['peso_cortes']) }}</div></td>
            <td><div class="etiqueta">Rinde</div><div class="valor">{{ $pct($r['rendimiento']) }}</div></td>
            <td><div class="etiqueta">Merma</div><div class="valor">{{ $kg(max(0, $r['peso_medias'] - $r['peso_cortes'])) }}</div></td>
        </tr>
        <tr>
            <td><div class="etiqueta">Costo</div><div class="valor">{{ $plata($r['costo_total']) }}</div></td>
            <td><div class="etiqueta">Valor de venta</div><div class="valor ambar">{{ $plata($r['valor_total']) }}</div></td>
            <td><div class="etiqueta">{{ $r['diferencia'] >= 0 ? 'Ganancia' : 'Pérdida' }}</div><div class="valor {{ $r['diferencia'] >= 0 ? 'verde' : 'rojo' }}">{{ $plata($r['diferencia']) }}</div></td>
            <td><div class="etiqueta">Margen</div><div class="valor">{{ $pct($margen) }}</div></td>
        </tr>
    </table>

    <h2>{{ ($r['modo'] ?? 'musculo') === 'cuarteo' ? 'Piezas' : 'Medias' }}</h2>
    <table class="tabla">
        <thead><tr><th>Media</th><th>Proveedor</th><th class="num">Kg</th><th class="num">$/kg</th><th class="num">Costo</th></tr></thead>
        <tbody>
            @foreach ($r['medias_detalle'] as $m)
                <tr>
                    <td>{{ $m['formato_etiqueta'] }} #{{ $m['id'] }}</td>
                    <td>{{ $m['proveedor'] ?: '-' }}</td>
                    <td class="num">{{ $kg($m['peso']) }}</td>
                    <td class="num">{{ $plata($m['precio_kg']) }}</td>
                    <td class="num">{{ $plata($m['costo']) }}</td>
                </tr>
            @endforeach
            <tr class="total"><td colspan="2">Total</td><td class="num">{{ $kg($r['peso_medias']) }}</td><td></td><td class="num">{{ $plata($r['costo_total']) }}</td></tr>
        </tbody>
    </table>

    <h2>Cortes</h2>
    <table class="tabla">
        <thead><tr><th>Corte</th><th class="num">Kg</th><th class="num">% kg</th><th class="num">$/kg</th><th class="num">Total</th></tr></thead>
        <tbody>
            @foreach ($r['cortes'] as $c)
                <tr>
                    <td>{{ \Illuminate\Support\Str::ucfirst($c['nombre']) }}</td>
                    <td class="num">{{ $kg($c['peso']) }}</td>
                    <td class="num">{{ $r['peso_medias'] > 0 ? $pct($c['peso'] / $r['peso_medias'] * 100) : '-' }}</td>
                    <td class="num">{{ $c['precio_kg'] !== null ? $plata($c['precio_kg']) : 'Sin precio' }}</td>
                    <td class="num">{{ $plata($c['total']) }}</td>
                </tr>
            @endforeach
            <tr class="total"><td>Total</td><td class="num">{{ $kg($r['peso_cortes']) }}</td><td class="num">{{ $pct($r['rendimiento']) }}</td><td></td><td class="num">{{ $plata($r['valor_total']) }}</td></tr>
        </tbody>
    </table>

    <div class="pie">Generado con Carnico · {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
