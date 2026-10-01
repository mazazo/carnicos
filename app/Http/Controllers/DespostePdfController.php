<?php

namespace App\Http\Controllers;

use App\Models\Desposte;
use App\Services\PdfDesposte;
use Symfony\Component\HttpFoundation\Response;

/** Descarga el PDF de un desposte terminado de la carnicería. */
class DespostePdfController extends Controller
{
    public function __invoke(Desposte $desposte, PdfDesposte $pdf): Response
    {
        abort_if($desposte->estaPendiente(), 404);

        return response($pdf->generar($desposte), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf->nombreArchivo($desposte).'"',
        ]);
    }
}
