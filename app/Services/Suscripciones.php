<?php

namespace App\Services;

use App\Models\Carniceria;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SuscripcionMovimiento;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Todo lo que cambia el acceso de una carnicería pasa por acá: la prueba
 * gratis, los pagos, los días sumados y los planes habilitados a mano. Cada
 * cambio queda en suscripcion_movimientos.
 */
class Suscripciones
{
    /** Prueba gratis al registrarse: N días con el plan de prueba (Plan 1). */
    public function iniciarPrueba(Carniceria $carniceria, ?User $user = null): Subscription
    {
        $plan = Plan::query()->where('codigo', config('carnicos.plan_prueba'))->firstOrFail();
        $dias = (int) config('carnicos.dias_prueba');
        $fin = now()->addDays($dias);

        $suscripcion = Subscription::query()->create([
            'carniceria_id' => $carniceria->id,
            'plan_id' => $plan->id,
            'user_id' => $user?->id,
            'plan' => $plan->codigo,
            'status' => Subscription::TRIAL,
            'origen' => 'prueba',
            'starts_at' => now(),
            'ends_at' => $fin,
            'trial_ends_at' => $fin,
        ]);

        $this->movimiento($carniceria, $suscripcion, 'prueba', "Prueba gratis de {$dias} días ({$plan->nombre}).", $user);

        return $suscripcion;
    }

    /**
     * Da acceso a un plan por un tiempo (meses o días). Si ya tiene ese mismo
     * plan pago y vigente, se SUMA al final (renovación anticipada, no se
     * pierden días). Si no, empieza ahora y reemplaza lo que tuviera.
     */
    public function activarPlan(
        Carniceria $carniceria,
        Plan $plan,
        int $meses = 0,
        int $dias = 0,
        string $origen = 'pago',
        ?float $precio = null,
        ?User $user = null,
    ): Subscription {
        if ($meses <= 0 && $dias <= 0) {
            throw new InvalidArgumentException('Indicá meses o días.');
        }

        return DB::transaction(function () use ($carniceria, $plan, $meses, $dias, $origen, $precio, $user): Subscription {
            $vigente = $carniceria->suscripcionVigente();
            $sumar = fn (Carbon $desde) => $desde->copy()->addMonthsNoOverflow($meses)->addDays($dias);
            $periodo = trim(($meses ? "{$meses} ".($meses === 1 ? 'mes' : 'meses') : '').($meses && $dias ? ' y ' : '').($dias ? "{$dias} días" : ''));

            if ($vigente && $vigente->status === Subscription::ACTIVE && (int) $vigente->plan_id === (int) $plan->id) {
                $vigente->update(['ends_at' => $sumar($vigente->ends_at), 'meses' => ($vigente->meses ?? 0) + $meses]);
                $this->movimiento($carniceria, $vigente, $origen === 'pago' ? 'pago' : 'plan_manual', "{$plan->nombre}: se suman {$periodo} (vence {$vigente->ends_at->format('d/m/Y')}).", $user);

                return $vigente;
            }

            if ($vigente) {
                $vigente->update(['status' => Subscription::CANCELLED, 'cancelled_at' => now()]);
            }

            $suscripcion = Subscription::query()->create([
                'carniceria_id' => $carniceria->id,
                'plan_id' => $plan->id,
                'user_id' => $user?->id,
                'plan' => $plan->codigo,
                'meses' => $meses ?: null,
                'precio' => $precio,
                'status' => Subscription::ACTIVE,
                'origen' => $origen,
                'starts_at' => now(),
                'ends_at' => $sumar(now()),
            ]);

            $this->movimiento($carniceria, $suscripcion, $origen === 'pago' ? 'pago' : 'plan_manual', "{$plan->nombre} por {$periodo} (vence {$suscripcion->ends_at->format('d/m/Y')}).", $user);

            return $suscripcion;
        });
    }

    /**
     * Suma días a lo que tenga (prueba o plan). Si ya venció, lo reabre desde
     * hoy; si nunca tuvo nada, le da una prueba de esos días.
     */
    public function sumarDias(Carniceria $carniceria, int $dias, ?User $user = null): Subscription
    {
        if ($dias < 1) {
            throw new InvalidArgumentException('Los días tienen que ser al menos 1.');
        }

        return DB::transaction(function () use ($carniceria, $dias, $user): Subscription {
            $suscripcion = $carniceria->suscripcionVigente();

            if ($suscripcion) {
                $fin = $suscripcion->ends_at->copy()->addDays($dias);
            } else {
                $suscripcion = $carniceria->suscripciones()->whereIn('status', [Subscription::TRIAL, Subscription::ACTIVE, Subscription::EXPIRED])->first();

                if (! $suscripcion) {
                    $plan = Plan::query()->where('codigo', config('carnicos.plan_prueba'))->firstOrFail();
                    $suscripcion = new Subscription([
                        'carniceria_id' => $carniceria->id,
                        'plan_id' => $plan->id,
                        'plan' => $plan->codigo,
                        'origen' => 'prueba',
                        'starts_at' => now(),
                    ]);
                }

                $fin = now()->addDays($dias);
                $suscripcion->status = $suscripcion->origen === 'prueba' ? Subscription::TRIAL : Subscription::ACTIVE;
            }

            $suscripcion->ends_at = $fin;
            if ($suscripcion->status === Subscription::TRIAL) {
                $suscripcion->trial_ends_at = $fin;
            }
            $suscripcion->save();

            $this->movimiento($carniceria, $suscripcion, 'dias', "Se sumaron {$dias} días (vence {$fin->format('d/m/Y')}).", $user);

            return $suscripcion;
        });
    }

    /** Pago aprobado (manual o Mercado Pago): activa el período pagado. Idempotente. */
    public function confirmarPago(Payment $payment, ?User $user = null): Payment
    {
        return DB::transaction(function () use ($payment, $user): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->isPaid()) {
                return $payment;
            }

            $suscripcion = $this->activarPlan(
                $payment->carniceria,
                $payment->plan,
                meses: (int) $payment->meses,
                origen: 'pago',
                precio: (float) $payment->amount,
                user: $user,
            );

            $payment->update([
                'status' => Payment::PAID,
                'paid_at' => now(),
                'subscription_id' => $suscripcion->id,
                'period_start' => $suscripcion->starts_at,
                'period_end' => $suscripcion->ends_at,
                'registrado_por' => $payment->registrado_por ?? $user?->id,
            ]);

            return $payment;
        });
    }

    public function rechazarPago(Payment $payment, string $motivo, ?User $user = null): void
    {
        if ($payment->isPaid()) {
            throw new InvalidArgumentException('Un pago aprobado no se puede rechazar.');
        }

        $payment->update(['status' => Payment::FAILED, 'notes' => trim(($payment->notes ? $payment->notes.' · ' : '').'Rechazado: '.$motivo)]);
        $this->movimiento($payment->carniceria, null, 'pago', "Pago #{$payment->id} rechazado: {$motivo}", $user);
    }

    /** Marca vencidas las suscripciones cuyo período terminó (tarea diaria). */
    public function vencer(): int
    {
        return Subscription::query()
            ->whereIn('status', [Subscription::TRIAL, Subscription::ACTIVE])
            ->where('ends_at', '<=', now())
            ->update(['status' => Subscription::EXPIRED]);
    }

    public function suspender(Carniceria $carniceria, string $motivo, ?User $user = null): void
    {
        $carniceria->update(['estado' => Carniceria::ESTADO_SUSPENDIDA]);
        $this->movimiento($carniceria, null, 'suspension', "Suspendida: {$motivo}", $user);
    }

    public function reactivar(Carniceria $carniceria, ?User $user = null): void
    {
        $carniceria->update(['estado' => Carniceria::ESTADO_ACTIVA]);
        $this->movimiento($carniceria, null, 'reactivacion', 'Reactivada.', $user);
    }

    public function movimiento(Carniceria $carniceria, ?Subscription $suscripcion, string $tipo, string $detalle, ?User $user = null): void
    {
        SuscripcionMovimiento::query()->create([
            'carniceria_id' => $carniceria->id,
            'subscription_id' => $suscripcion?->id,
            'tipo' => $tipo,
            'detalle' => mb_substr($detalle, 0, 255),
            'user_id' => $user?->id,
        ]);
    }
}
