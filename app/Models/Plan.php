<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Plan de la plataforma: define QUÉ puede usar una carnicería (tipos de
 * animal, usuarios, dashboard, app Android). El TIEMPO y el precio están en
 * plan_precios (1, 3, 6, 12 meses…).
 */
class Plan extends Model
{
    public const ESTILO_NORMAL = 'normal';

    public const ESTILO_DESTACADO = 'destacado';

    public const ESTILO_PREMIUM = 'premium';

    protected $table = 'planes';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'max_tipos_animal',
        'max_usuarios',
        'incluye_dashboard',
        'incluye_app',
        'caracteristicas',
        'estilo',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'max_tipos_animal' => 'integer',
            'max_usuarios' => 'integer',
            'incluye_dashboard' => 'boolean',
            'incluye_app' => 'boolean',
            'caracteristicas' => 'array',
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function precios(): HasMany
    {
        return $this->hasMany(PlanPrecio::class)->orderBy('meses');
    }

    /** Precio del período mensual (el que se muestra como referencia). */
    public function precioMensual(): HasOne
    {
        return $this->hasOne(PlanPrecio::class)->where('meses', 1)->where('activo', true);
    }

    public function scopeActivos(Builder $query): void
    {
        $query->where('activo', true)->orderBy('orden')->orderBy('id');
    }

    /** ¿Puede tener un usuario más, teniendo ya $actuales? */
    public function permiteUsuarios(int $actuales): bool
    {
        return $this->max_usuarios === null || $actuales < $this->max_usuarios;
    }

    /**
     * Lo que incluye, en textos para la página de planes (se arma de los
     * límites + las características extra cargadas por el administrador).
     *
     * @return list<string>
     */
    public function incluye(): array
    {
        $tipos = match (true) {
            $this->max_tipos_animal === null || $this->max_tipos_animal >= 3 => 'Vacuno, porcino y aviar',
            $this->max_tipos_animal === 1 => '1 tipo de animal a elección (vacuno, porcino o aviar)',
            default => "{$this->max_tipos_animal} tipos de animal a elección",
        };

        $usuarios = match (true) {
            $this->max_usuarios === null => 'Usuarios ilimitados',
            $this->max_usuarios === 1 => '1 usuario',
            default => "Hasta {$this->max_usuarios} usuarios",
        };

        return array_values(array_filter([
            $tipos,
            $usuarios,
            $this->incluye_dashboard ? 'Dashboard con estadísticas' : null,
            $this->incluye_app ? 'App Android' : null,
            ...($this->caracteristicas ?? []),
        ]));
    }

    /** Lo que NO incluye (para mostrarlo tachado). @return list<string> */
    public function noIncluye(): array
    {
        return array_values(array_filter([
            $this->incluye_dashboard ? null : 'Dashboard con estadísticas',
            $this->incluye_app ? null : 'App Android',
        ]));
    }
}
