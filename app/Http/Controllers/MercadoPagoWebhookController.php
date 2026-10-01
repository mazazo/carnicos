<?php

namespace App\Http\Controllers;

use App\Actions\ProcesarPagoMercadoPago;
use App\Models\WebhookEvento;
use App\Services\MercadoPago;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Avisos de Mercado Pago. Se valida la firma, se guarda el aviso (una sola vez
 * por x-request-id) y, si es de un pago, se procesa consultándolo en la API.
 * Siempre responde rápido; un error queda registrado en webhook_eventos.
 */
class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, MercadoPago $mercadoPago, ProcesarPagoMercadoPago $procesar): JsonResponse
    {
        if (! $mercadoPago->firmaValida($request)) {
            return response()->json(['ok' => false, 'message' => 'Firma inválida'], 401);
        }

        $tipo = (string) ($request->input('type') ?? $request->query('type') ?? $request->input('topic', ''));
        $recursoId = (string) ($request->input('data.id') ?? $request->query('data_id') ?? '');

        try {
            $evento = WebhookEvento::query()->create([
                'proveedor' => 'mercadopago',
                'evento_id' => (string) ($request->header('x-request-id') ?: $request->input('id') ?: sha1($request->getContent())),
                'tipo' => $tipo,
                'recurso_id' => $recursoId,
                'payload' => $request->all(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['ok' => true, 'repetido' => true]); // ya lo recibimos
        }

        if ($tipo !== 'payment' || $recursoId === '') {
            $evento->update(['estado' => 'ignorado', 'procesado_at' => now()]);

            return response()->json(['ok' => true]);
        }

        try {
            $procesar->handle($recursoId);
            $evento->update(['estado' => 'procesado', 'procesado_at' => now()]);
        } catch (Throwable $e) {
            report($e);
            $evento->update(['estado' => 'error', 'error' => mb_substr($e->getMessage(), 0, 500)]);
        }

        return response()->json(['ok' => true]);
    }
}
