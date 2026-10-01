<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\CutCatalog;
use App\Models\Desposte;
use App\Models\DesposteCorte;
use App\Models\DespostePesada;
use App\Models\PrecioCorte;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Despostes de la carnicería, iguales desde la app y desde la web: se inician
 * pendientes con las medias (que quedan en_desposte), se cargan pesadas por
 * corte y al terminarlos se suman por corte con el precio por kg vigente.
 *
 * Modos (Desposte::MODOS): por músculo (media → músculos, el de la app); por
 * piezas grandes (media → cortes primarios, que al terminar quedan como piezas
 * disponibles con el costo repartido por kg); cuarteo (pieza → sus músculos).
 */
class Despostes
{
    /** @param  array<int, int>  $animalIds */
    public function iniciar(User $user, int $tipoId, array $animalIds, string $modo = Desposte::MODO_MUSCULO): Desposte
    {
        $this->exigirTipo($user, $tipoId);
        if (! array_key_exists($modo, Desposte::MODOS)) {
            throw ValidationException::withMessages(['modo' => 'Modo de desposte inválido.']);
        }
        if ($modo === Desposte::MODO_PRIMARIO && ! $this->tienePiezasGrandes($user, $tipoId)) {
            throw ValidationException::withMessages(['modo' => 'Este animal no tiene piezas grandes habilitadas.']);
        }
        if ($animalIds === []) {
            throw ValidationException::withMessages(['animal_ids' => $modo === Desposte::MODO_CUARTEO ? 'Elegí al menos una pieza.' : 'Elegí al menos una media.']);
        }

        return DB::transaction(function () use ($user, $tipoId, $animalIds, $modo): Desposte {
            $medias = $this->tomarMedias($animalIds, $tipoId, $modo);

            $desposte = Desposte::query()->create([
                'animal_type_id' => $tipoId,
                'estado' => Desposte::PENDIENTE,
                'modo' => $modo,
                'animal_id' => $medias->count() === 1 ? $medias->first()->id : null,
                'user_id' => $user->id,
                'despostador_nombre' => $user->full_name,
                'fecha_desposte' => today()->toDateString(),
            ]);
            $this->adjuntarMedias($desposte, $medias);
            Animal::query()->whereIn('id', $medias->pluck('id'))->update(['estado' => Animal::EN_DESPOSTE]);

            return $desposte;
        });
    }

    public function agregarPesada(User $user, Desposte $desposte, int $corteId, float $peso): DespostePesada
    {
        $this->exigirPendiente($desposte);
        $this->exigirCorte($user, $desposte, $corteId);
        $this->exigirPeso($peso);

        return $desposte->pesadas()->create(['cut_catalog_id' => $corteId, 'peso' => $peso, 'user_id' => $user->id]);
    }

    /**
     * Deja en [peso] el total de un corte (la web carga el total, no pesadas
     * sueltas): reemplaza sus pesadas por una sola, o las borra con 0.
     */
    public function fijarPesoCorte(User $user, Desposte $desposte, int $corteId, float $peso): void
    {
        $this->exigirPendiente($desposte);
        $this->exigirCorte($user, $desposte, $corteId);
        if ($peso < 0 || $peso > 99999) {
            throw ValidationException::withMessages(['peso' => 'Revisá el peso del corte.']);
        }

        DB::transaction(function () use ($user, $desposte, $corteId, $peso): void {
            $desposte->pesadas()->where('cut_catalog_id', $corteId)->delete();
            if ($peso > 0) {
                $desposte->pesadas()->create(['cut_catalog_id' => $corteId, 'peso' => $peso, 'user_id' => $user->id]);
            }
        });
    }

    public function quitarPesada(Desposte $desposte, int $pesadaId): void
    {
        $this->exigirPendiente($desposte);
        $desposte->pesadas()->findOrFail($pesadaId)->delete();
    }

    /** Suma las pesadas por corte, guarda los cortes con el precio vigente y desposta las medias. */
    public function terminar(Desposte $desposte): Desposte
    {
        DB::transaction(function () use ($desposte): void {
            $bloqueado = Desposte::query()->whereKey($desposte->id)->lockForUpdate()->firstOrFail();
            $this->exigirPendiente($bloqueado);

            $pesos = $bloqueado->pesadas()->get()->groupBy('cut_catalog_id')
                ->map(fn ($pesadas) => round($pesadas->sum(fn (DespostePesada $p) => (float) $p->peso), 3));
            if ($pesos->isEmpty()) {
                throw ValidationException::withMessages(['pesadas' => 'Agregá los kg de al menos un corte.']);
            }

            $catalogo = CutCatalog::query()->whereIn('id', $pesos->keys())->pluck('nombre_canonico', 'id');
            $this->crearCortes($bloqueado, $pesos->map(fn ($peso, $id) => ['cut_catalog_id' => $id, 'peso' => $peso])->values()->all(), $catalogo);

            $bloqueado->update(['estado' => Desposte::TERMINADO, 'fecha_desposte' => today()->toDateString()]);
            $bloqueado->guardarCalculo();
            if ($bloqueado->modo === Desposte::MODO_PRIMARIO) {
                $this->crearPiezas($bloqueado);
            }
            Animal::query()->whereIn('id', $bloqueado->animales()->pluck('animals.id'))->update(['estado' => Animal::DESPOSTADA]);
        });

        return $desposte->refresh();
    }

    /** Cancela un desposte pendiente: las medias vuelven a disponibles. */
    public function cancelar(Desposte $desposte): void
    {
        DB::transaction(function () use ($desposte): void {
            $bloqueado = Desposte::query()->whereKey($desposte->id)->lockForUpdate()->firstOrFail();
            $this->exigirPendiente($bloqueado);

            Animal::query()->whereIn('id', $bloqueado->animales()->pluck('animals.id'))->update(['estado' => Animal::DISPONIBLE]);
            $bloqueado->delete();
        });
    }

    /**
     * Desposte terminado en un solo paso (API vieja: medias y cortes juntos).
     *
     * @param  array<int, int>  $animalIds
     * @param  array<int, array{cut_catalog_id: int, peso: float, cantidad?: int|null}>  $cortes
     */
    public function registrarTerminado(User $user, int $tipoId, array $animalIds, array $cortes, ?string $fecha = null, ?string $observaciones = null): Desposte
    {
        $this->exigirTipo($user, $tipoId);

        $catalogo = CutCatalog::query()->where('animal_type_id', $tipoId)->where('nivel', CutCatalog::NIVEL_MUSCULO)
            ->habilitadosPara((int) $user->carniceria_id)
            ->whereIn('id', array_column($cortes, 'cut_catalog_id'))->pluck('nombre_canonico', 'id');
        if ($catalogo->count() !== count($cortes)) {
            throw ValidationException::withMessages(['cortes' => 'Hay cortes que no son de este tipo de animal.']);
        }

        return DB::transaction(function () use ($user, $tipoId, $animalIds, $cortes, $fecha, $observaciones, $catalogo): Desposte {
            $medias = $this->tomarMedias($animalIds, $tipoId);

            $desposte = Desposte::query()->create([
                'animal_type_id' => $tipoId,
                'animal_id' => $medias->count() === 1 ? $medias->first()->id : null,
                'user_id' => $user->id,
                'despostador_nombre' => $user->full_name,
                'fecha_desposte' => $fecha ?? today()->toDateString(),
                'observaciones' => $observaciones,
            ]);
            $this->adjuntarMedias($desposte, $medias);
            $this->crearCortes($desposte, $cortes, $catalogo);
            $desposte->guardarCalculo();
            Animal::query()->whereIn('id', $medias->pluck('id'))->update(['estado' => Animal::DESPOSTADA]);

            return $desposte;
        });
    }

    /** Precio por kg vigente de un corte; solo el dueño lo cambia. */
    public function fijarPrecio(User $user, int $corteId, float $precio): void
    {
        abort_unless($user->esDueno(), 403, 'Solo el dueño puede cambiar los precios.');
        if ($precio < 0 || $precio > 99999999) {
            throw ValidationException::withMessages(['precio' => 'Revisá el precio.']);
        }
        abort_unless(CutCatalog::query()->whereKey($corteId)->whereIn('animal_type_id', $user->carniceria->tiposAnimalHabilitadosIds())->exists(), 404);

        PrecioCorte::query()->updateOrCreate(
            ['carniceria_id' => $user->carniceria_id, 'cut_catalog_id' => $corteId],
            ['precio_kg' => $precio, 'user_id' => $user->id],
        );
    }

    /** Valor de lo pesado en un desposte pendiente, con los precios vigentes. */
    public function valorPesadas(Desposte $desposte): float
    {
        if ($desposte->pesadas->isEmpty()) {
            return 0.0;
        }
        $precios = PrecioCorte::query()->whereIn('cut_catalog_id', $desposte->pesadas->pluck('cut_catalog_id'))->pluck('precio_kg', 'cut_catalog_id');

        return (float) $desposte->pesadas->sum(fn (DespostePesada $p) => (float) $p->peso * (float) ($precios[$p->cut_catalog_id] ?? 0));
    }

    public function exigirPendiente(Desposte $desposte): void
    {
        if (! $desposte->estaPendiente()) {
            throw ValidationException::withMessages(['estado' => 'Este desposte ya está terminado.']);
        }
    }

    private function exigirTipo(User $user, int $tipoId): void
    {
        if (! in_array($tipoId, array_map('intval', $user->carniceria?->tiposAnimalHabilitadosIds() ?? []), true)) {
            throw ValidationException::withMessages(['animal_type_id' => 'Tu plan no incluye este tipo de animal.']);
        }
    }

    /** El corte tiene que ser del animal y del nivel del modo: piezas grandes o músculos. */
    private function exigirCorte(User $user, Desposte $desposte, int $corteId): void
    {
        $nivel = $desposte->modo === Desposte::MODO_PRIMARIO ? CutCatalog::NIVEL_PRIMARIO : CutCatalog::NIVEL_MUSCULO;
        $existe = CutCatalog::query()->whereKey($corteId)->where('nivel', $nivel)
            ->where('animal_type_id', $desposte->animal_type_id)->habilitadosPara((int) $user->carniceria_id)->exists();
        if (! $existe) {
            throw ValidationException::withMessages(['cut_catalog_id' => $nivel === CutCatalog::NIVEL_PRIMARIO
                ? 'En un desposte por piezas grandes solo van piezas grandes.'
                : 'El corte no es de este tipo de animal.']);
        }
    }

    public function tienePiezasGrandes(User $user, int $tipoId): bool
    {
        return CutCatalog::query()->where('animal_type_id', $tipoId)->where('nivel', CutCatalog::NIVEL_PRIMARIO)
            ->habilitadosPara((int) $user->carniceria_id)->exists();
    }

    /**
     * Cada pieza grande del desposte queda como ingreso disponible para cuartear.
     * Su costo por kg es el del desposte repartido entre los kg obtenidos
     * (la merma la absorben las piezas).
     */
    private function crearPiezas(Desposte $desposte): void
    {
        $costoKg = $desposte->kg_cortes > 0 ? round($desposte->costo / $desposte->kg_cortes, 2) : 0;
        $origen = $desposte->animales()->first();

        foreach ($desposte->cortes()->get() as $corte) {
            Animal::query()->create([
                'carniceria_id' => $desposte->carniceria_id,
                'user_id' => $desposte->user_id,
                'animal_type_id' => $desposte->animal_type_id,
                'formato' => Animal::FORMATO_PIEZA,
                'cut_catalog_id' => $corte->cut_catalog_id,
                'desposte_origen_id' => $desposte->id,
                'cantidad' => 1,
                'peso_total' => $corte->peso,
                'precio_kg' => $costoKg,
                'fecha' => $desposte->fecha_desposte,
                'proveedor' => $origen?->proveedor,
                'frigorifico' => $origen?->frigorifico,
                'estado' => Animal::DISPONIBLE,
            ]);
        }
    }

    private function exigirPeso(float $peso): void
    {
        if ($peso <= 0) {
            throw ValidationException::withMessages(['peso' => 'El peso tiene que ser mayor a 0.']);
        }
        if ($peso > 99999) {
            throw ValidationException::withMessages(['peso' => 'Revisá el peso.']);
        }
    }

    /**
     * Ingresos disponibles del tipo, bloqueados para la transacción: medias o
     * reses para despostar, o piezas grandes para el cuarteo.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, Animal>
     */
    private function tomarMedias(array $ids, int $tipoId, string $modo = Desposte::MODO_MUSCULO): Collection
    {
        $medias = Animal::query()->whereIn('id', $ids)->where('animal_type_id', $tipoId)
            ->when($modo === Desposte::MODO_CUARTEO, fn ($q) => $q->piezas(), fn ($q) => $q->sinPiezas())
            ->disponibles()->lockForUpdate()->get();

        if ($medias->count() !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['animal_ids' => $modo === Desposte::MODO_CUARTEO
                ? 'Alguna pieza ya se usó o no es de este tipo. Actualizá el listado.'
                : 'Alguna media ya fue despostada o no es de este tipo. Actualizá el listado.']);
        }

        return $medias;
    }

    /** @param  Collection<int, Animal>  $medias */
    private function adjuntarMedias(Desposte $desposte, Collection $medias): void
    {
        $desposte->animales()->attach($medias->mapWithKeys(fn (Animal $m) => [
            $m->id => ['peso' => $m->peso_total, 'precio_kg' => $m->precio_kg],
        ])->all());
    }

    /**
     * @param  array<int, array{cut_catalog_id: int, peso: float, cantidad?: int|null}>  $cortes
     * @param  \Illuminate\Support\Collection<int, string>  $catalogo  id → nombre
     */
    private function crearCortes(Desposte $desposte, array $cortes, $catalogo): void
    {
        $precios = PrecioCorte::query()->pluck('precio_kg', 'cut_catalog_id');
        foreach ($cortes as $corte) {
            DesposteCorte::query()->create([
                'desposte_id' => $desposte->id,
                'cut_catalog_id' => $corte['cut_catalog_id'],
                'nombre' => $catalogo[$corte['cut_catalog_id']],
                'cantidad' => $corte['cantidad'] ?? 1,
                'peso' => $corte['peso'],
                'precio_kg' => $precios[$corte['cut_catalog_id']] ?? null,
            ]);
        }
    }
}
