<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\AccesoApp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Rutas de la app: 403 con el código del bloqueo si el plan no incluye la app. */
class EnsureAccesoApp
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();
        $acceso = AccesoApp::para($user);

        if (! $acceso->permitido()) {
            return response()->json(['message' => $acceso->mensaje, 'codigo' => $acceso->codigo], 403);
        }

        return $next($request);
    }
}
