<?php

namespace App\Services;

use App\Http\Controllers\Api\ProduccionController;
use App\Models\Desposte;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;

/** PDF del resumen de un desposte terminado (los valores que quedaron guardados). */
class PdfDesposte
{
    public function generar(Desposte $desposte): string
    {
        $desposte->loadMissing(['animales', 'cortes', 'animalType', 'pesadas', 'carniceria']);

        $html = view('pdf.desposte', [
            'r' => ProduccionController::detalle($desposte),
            'carniceria' => $desposte->carniceria?->nombre ?? 'Carnico',
        ])->render();

        $opciones = new Options;
        $opciones->set('defaultFont', 'DejaVu Sans');
        $opciones->set('isRemoteEnabled', false);

        $pdf = new Dompdf($opciones);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }

    public function nombreArchivo(Desposte $desposte): string
    {
        return 'desposte-'.Str::slug($desposte->animalType?->nombre ?? 'animal').'-'.$desposte->id.'.pdf';
    }
}
