<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Descarga del APK de la app Android. Solo para planes con app (Plan Completo)
 * y usuarios con permiso de app. El archivo está en
 * storage/app/private/apk/carnico.apk (fuera de public: no hay enlace directo).
 */
class AppAndroidController extends Controller
{
    public const ARCHIVO = 'apk/carnico.apk';

    public function descargar(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->puedeDescargarApp(), 403, 'La app Android está incluida en el Plan Completo y necesita el permiso de app.');
        abort_unless(Storage::disk('local')->exists(self::ARCHIVO), 404, 'Todavía no hay una versión de la app para descargar.');

        return Storage::disk('local')->download(self::ARCHIVO, 'carnico.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}
