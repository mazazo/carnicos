<?php

namespace App\Observers;

use App\Models\Subscription;

class SubscriptionObserver
{
    /**
     * Cuando se crea o actualiza una suscripción activa/trial,
     * sincroniza el campo users.plan para queries rápidas.
     */
    public function saved(Subscription $subscription): void
    {
        $this->syncUserPlan($subscription);
    }

    /**
     * Si se cancela o expira, vuelve a starter.
     */
    public function deleted(Subscription $subscription): void
    {
        $subscription->user?->update(['plan' => 'starter']);
    }

    private function syncUserPlan(Subscription $subscription): void
    {
        // Solo sincroniza si es la suscripción activa/trial
        if (in_array($subscription->status, ['active', 'trial'])) {
            $subscription->user?->update(['plan' => $subscription->plan]);
        } elseif (in_array($subscription->status, ['cancelled', 'expired'])) {
            // Buscar si hay otra suscripción activa, sino starter
            $activePlan = $subscription->user?->subscriptions()
                ->whereIn('status', ['active', 'trial'])
                ->latest()
                ->value('plan');

            $subscription->user?->update(['plan' => $activePlan ?? 'starter']);
        }
    }
}
