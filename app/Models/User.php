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

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
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
        ];
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

    public function isPro(): bool
    {
        return $this->plan === 'pro';
    }

    public function isEnterprise(): bool
    {
        return $this->plan === 'enterprise';
    }

    public function isStarter(): bool
    {
        return $this->plan === 'starter';
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function dashboardRouteName(): string
    {
        if ($this->isAdmin()) {
            return 'dashboard.enterprise';
        }

        if ($this->isEnterprise()) {
            return 'dashboard.enterprise';
        }

        if ($this->isPro()) {
            return 'dashboard.pro';
        }

        return 'billing.plans';
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
