<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Models\CutUserPrice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'carniceria_id',
        'rol',
        'permisos',
        'name',
        'last_name',
        'email',
        'plan',
        'is_admin',
        'movil',
        'password',
    ];

    protected $appends = [
        'full_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'permisos' => 'array',
        ];
    }

    public const ROL_DUENO = 'dueno';
    public const ROL_EMPLEADO = 'empleado';

    /** Lo que el dueño le habilita a cada empleado (el dueño tiene todo). */
    public const PERMISOS = [
        'ingresos' => 'Ingresos',
        'producciones' => 'Producciones',
        'cortes' => 'Cortes',
        'app' => 'App del celular',
    ];

    /** Puede bajar la app Android: plan con app (Plan Completo) y permiso de app. */
    public function puedeDescargarApp(): bool
    {
        return $this->isAdmin() || ((bool) $this->planVigente()?->incluye_app && $this->puede('app'));
    }

    /** ¿Puede usar esta parte del sistema? El dueño y el administrador, siempre. */
    public function puede(string $permiso): bool
    {
        if ($this->esDueno() || $this->isAdmin()) {
            return true;
        }

        return in_array($permiso, $this->permisos ?? [], true);
    }

    /** Carnicería a la que pertenece (NULL: administrador de la plataforma). */
    public function carniceria(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Carniceria::class);
    }

    public function esDueno(): bool
    {
        return $this->rol === self::ROL_DUENO;
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function cutCatalogs(): HasMany
    {
        return $this->hasMany(CutCatalog::class);
    }

    public function cutPrices(): HasMany
    {
        return $this->hasMany(CutUserPrice::class);
    }

    public function cuarteos(): HasMany
    {
        return $this->hasMany(Cuarteo::class);
    }

    /** Suscripción activa actual */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trial'])
            ->latest();
    }

    /** Historial completo de suscripciones */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** Todos los pagos del usuario */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Plan vigente de su carnicería (prueba o pagado); null si no tiene. */
    public function planVigente(): ?Plan
    {
        return $this->carniceria?->planVigente();
    }

    /** Su plan incluye el dashboard con estadísticas (Plan 3). */
    public function tieneDashboardCompleto(): bool
    {
        return (bool) $this->planVigente()?->incluye_dashboard;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * A dónde entra: el admin de la plataforma a su configuración; una
     * carnicería con plan vigente a su inicio (dashboard completo solo si el
     * plan lo incluye); sin plan vigente, a la página de planes.
     */
    public function dashboardRouteName(): string
    {
        if ($this->isAdmin()) {
            return $this->carniceria_id ? 'dashboard.enterprise' : 'admin.config.index';
        }

        if (! $this->planVigente()) {
            return 'billing.plans';
        }

        return $this->tieneDashboardCompleto() ? 'dashboard.enterprise' : 'dashboard.pro';
    }

    public function canDebugDashboards(): bool
    {
        return $this->isAdmin() && (bool) config('app.debug');
    }

    public function getFullNameAttribute(): string
    {
        return trim((string) ($this->name . ' ' . ($this->last_name ?? '')));
    }
}
