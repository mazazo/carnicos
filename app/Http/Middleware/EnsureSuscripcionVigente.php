<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueo total: sin prueba ni plan vigente (o con la cuenta suspendida) la
 * carnicería solo puede ver la página de planes y pagar. Si su plan limita
 * los tipos de animal y todavía no eligió, primero tiene que elegirlos.
 * El administrador de la plataforma no tiene estas restricciones.
 */
class EnsureSuscripcionVigente
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        $carniceria = $user->carniceria;

        if (! $carniceria || ! $carniceria->estaActiva()) {
            return redirect()->route('billing.plans')
                ->with('error', 'Tu cuenta está suspendida. Comunicate con administración.');
        }

        if (! $carniceria->suscripcionVigente()) {
            return redirect()->route('billing.plans')
                ->with('error', 'Tu prueba o tu plan venció. Elegí un plan para seguir usando el sistema.');
        }

        if ($carniceria->debeElegirTiposAnimal() && ! $request->routeIs('cuenta.tipos-animal')) {
            return redirect()->route('cuenta.tipos-animal');
        }

        return $next($request);
    }
}
