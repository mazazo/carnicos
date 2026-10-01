<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * El cliente de la plataforma: una carnicería con uno o más usuarios.
 * Sus animales, catálogo propio y precios le pertenecen a ella.
 */
class Carniceria extends Model
{
    public const ESTADO_ACTIVA = 'activa';

    public const ESTADO_SUSPENDIDA = 'suspendida';

    protected $table = 'carnicerias';

    protected $fillable = [
        'nombre',
        'razon_social',
        'cuit',
        'email',
        'telefono',
        'estado',
    ];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function dueno(): HasOne
    {
        return $this->hasOne(User::class)->where('rol', User::ROL_DUENO)->oldestOfMany();
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function estaActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVA;
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest('id');
    }

    /** La suscripción que hoy le da acceso (prueba o pagada), si la hay. */
    public function suscripcionVigente(): ?Subscription
    {
        return $this->hasMany(Subscription::class)->vigentes()->with('planModel')->latest('ends_at')->first();
    }

    /** Plan con el que trabaja hoy (el de su suscripción vigente). */
    public function planVigente(): ?Plan
    {
        return $this->suscripcionVigente()?->planModel;
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(SuscripcionMovimiento::class)->latest('id');
    }

    /** Tipos de animal que eligió según su plan (Plan 1: uno; Plan 2: dos). */
    public function tiposAnimal(): BelongsToMany
    {
        return $this->belongsToMany(AnimalType::class, 'carniceria_tipos_animal')->withTimestamps();
    }

    /**
     * IDs de tipos de animal que puede usar hoy: con un plan que los limita,
     * los elegidos; si el plan incluye todos, todos los activos.
     *
     * @return list<int>
     */
    public function tiposAnimalHabilitadosIds(): array
    {
        $plan = $this->planVigente();
        $activos = AnimalType::query()->where('activo', true)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($plan === null || $plan->max_tipos_animal === null || $plan->max_tipos_animal >= count($activos)) {
            return $activos;
        }

        return $this->tiposAnimal()->where('animal_types.activo', true)->pluck('animal_types.id')->map(fn ($id) => (int) $id)->all();
    }

    /** Su plan limita los tipos y todavía no eligió (o eligió de más, tras bajar de plan). */
    public function debeElegirTiposAnimal(): bool
    {
        $plan = $this->planVigente();

        if ($plan === null || $plan->max_tipos_animal === null) {
            return false;
        }

        $activos = AnimalType::query()->where('activo', true)->count();

        if ($plan->max_tipos_animal >= $activos) {
            return false;
        }

        $elegidos = $this->tiposAnimal()->count();

        return $elegidos === 0 || $elegidos > $plan->max_tipos_animal;
    }
}
