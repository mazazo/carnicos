<?php

namespace App\Support;

use App\Models\User;

/**
 * ¿Puede este usuario usar la app Android? Solo una carnicería activa, con
 * email verificado y un plan vigente que incluya la app (Plan 3). El código
 * le dice a la app qué pantalla de bloqueo mostrar.
 */
final class AccesoApp
{
    public const SIN_CARNICERIA = 'sin_carniceria';

    public const EMAIL_NO_VERIFICADO = 'email_no_verificado';

    public const SUSPENDIDA = 'suspendida';

    public const SIN_SUSCRIPCION = 'sin_suscripcion';

    public const PLAN_SIN_APP = 'plan_sin_app';

    /** Empleado sin el permiso "App del celular". */
    public const SIN_PERMISO_APP = 'sin_permiso_app';

    private function __construct(public readonly ?string $codigo, public readonly ?string $mensaje) {}

    public static function para(User $user): self
    {
        $carniceria = $user->carniceria;

        return match (true) {
            $carniceria === null => new self(self::SIN_CARNICERIA, 'Tu usuario no pertenece a una carnicería.'),
            ! $user->hasVerifiedEmail() => new self(self::EMAIL_NO_VERIFICADO, 'Confirmá tu email con el enlace que te enviamos para empezar a usar Carnico.'),
            ! $carniceria->estaActiva() => new self(self::SUSPENDIDA, 'Tu cuenta está suspendida. Comunicate con administración.'),
            $carniceria->suscripcionVigente() === null => new self(self::SIN_SUSCRIPCION, 'Tu prueba o tu plan venció. Renová tu plan en carniox.com para seguir usando la app.'),
            ! $carniceria->planVigente()?->incluye_app => new self(self::PLAN_SIN_APP, 'Tu plan no incluye la app. Pasate al Plan Completo en carniox.com para usarla.'),
            ! $user->puede('app') => new self(self::SIN_PERMISO_APP, 'Tu usuario no tiene acceso a la app. Pedíselo al dueño de la carnicería.'),
            default => new self(null, null),
        };
    }

    public function permitido(): bool
    {
        return $this->codigo === null;
    }

    /** @return array{permitido: bool, codigo: string|null, mensaje: string|null} */
    public function toArray(): array
    {
        return ['permitido' => $this->permitido(), 'codigo' => $this->codigo, 'mensaje' => $this->mensaje];
    }
}
