<?php

namespace Tests\Feature\Concerns;

use App\Models\AnimalType;
use App\Models\Carniceria;
use App\Models\Plan;
use App\Models\User;
use App\Services\Suscripciones;

trait CreaCarnicerias
{
    /** Los tres tipos de animal activos (vacuno, porcino, aviar). @return list<AnimalType> */
    protected function tiposDeAnimal(): array
    {
        return collect(['vacuno', 'porcino', 'aviar'])
            ->map(fn (string $nombre) => AnimalType::query()->firstOrCreate(['nombre' => $nombre], ['activo' => true]))
            ->all();
    }

    /**
     * Carnicería con su dueño. $plan: 'prueba' (prueba gratis), un código de
     * plan (activo 1 mes) o null (sin acceso).
     *
     * @return array{0: Carniceria, 1: User}
     */
    protected function carniceriaCon(?string $plan = 'prueba', string $nombre = 'Don Juan'): array
    {
        $carniceria = Carniceria::query()->create(['nombre' => $nombre]);
        $dueno = User::factory()->create(['carniceria_id' => $carniceria->id, 'rol' => User::ROL_DUENO]);

        if ($plan === 'prueba') {
            app(Suscripciones::class)->iniciarPrueba($carniceria, $dueno);
        } elseif ($plan !== null) {
            app(Suscripciones::class)->activarPlan($carniceria, $this->plan($plan), meses: 1, origen: 'manual');
        }

        return [$carniceria, $dueno];
    }

    protected function plan(string $codigo): Plan
    {
        return Plan::query()->where('codigo', $codigo)->sole();
    }

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }
}
