<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ruta para usuarios con un permiso (ingresos, producciones, cortes) o solo
 * para el dueño ("permiso:dueno"). En la API responde 403 sin código de
 * bloqueo, para que la app lo muestre como un mensaje y no bloquee todo.
 */
class EnsurePermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        $permitido = $user !== null && ($permiso === 'dueno'
            ? $user->esDueno() || $user->isAdmin()
            : $user->puede($permiso));

        if (! $permitido) {
            $mensaje = $permiso === 'dueno'
                ? 'Esta parte es solo para el dueño de la carnicería.'
                : 'No tenés permiso para '.mb_strtolower(User::PERMISOS[$permiso] ?? $permiso).'. Pedíselo al dueño.';

            abort(403, $mensaje);
        }

        return $next($request);
    }
}
