<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
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

    public const ANTERIOR = 'apk/carnico-anterior.apk';

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

    /**
     * El administrador publica una versión nueva del APK. La anterior queda
     * como apk/carnico-anterior.apk por si hay que volver atrás.
     */
    public function subir(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $request->validate([
            'apk' => ['required', 'file', 'extensions:apk', 'max:204800'], // hasta 200 MB
        ], [
            'apk.required' => 'Elegí el archivo .apk. Si lo elegiste y aparece este error, es más grande de lo que permite el servidor (upload_max_filesize / post_max_size).',
            'apk.extensions' => 'Tiene que ser el archivo .apk de la app.',
            'apk.max' => 'El archivo pasa los 200 MB.',
        ]);

        $disco = Storage::disk('local');
        if ($disco->exists(self::ARCHIVO)) {
            $disco->delete(self::ANTERIOR);
            $disco->move(self::ARCHIVO, self::ANTERIOR);
        }
        $request->file('apk')->storeAs(dirname(self::ARCHIVO), basename(self::ARCHIVO), 'local');

        return redirect()->route('app.android')->with('success', 'Versión nueva publicada. Ya la pueden descargar.');
    }
}
