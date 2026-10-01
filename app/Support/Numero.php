<?php

namespace App\Support;

/** Números escritos a la argentina: punto de miles y coma decimal (igual que la app). */
class Numero
{
    /** "1.234,5", "9.000" (miles) o "1234.5" → número; vacío → 0; inválido → null. */
    public static function leer(?string $texto): ?float
    {
        $t = str_replace([' ', '$'], '', trim((string) $texto));
        if ($t === '') {
            return 0.0;
        }
        if (str_contains($t, ',')) {
            $t = str_replace(['.', ','], ['', '.'], $t);
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $t)) {
            $t = str_replace('.', '', $t);
        }

        return is_numeric($t) ? (float) $t : null;
    }

    /** 9800 → "9.800"; 22.5 → "22,5" (sin ceros de más). */
    public static function texto(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 3, ',', '.'), '0'), ',');
    }
}
