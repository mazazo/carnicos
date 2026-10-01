<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Suscripción de una carnicería: la prueba gratis o un período pagado (o
 * habilitado a mano por el administrador). Vigente = trial/active y ends_at futuro.
 */
class Subscription extends Model
{
    public const TRIAL = 'trial';

    public const ACTIVE = 'active';

    public const EXPIRED = 'expired';

    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'carniceria_id',
        'plan_id',
        'user_id',
        'plan',
        'meses',
        'precio',
        'status',
        'origen',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'meses' => 'integer',
        'precio' => 'decimal:2',
    ];

    public function carniceria(): BelongsTo
    {
        return $this->belongsTo(Carniceria::class);
    }

    public function planModel(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeVigentes(Builder $query): void
    {
        $query->whereIn('status', [self::TRIAL, self::ACTIVE])->where('ends_at', '>', now());
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::TRIAL, self::ACTIVE], true)
            && $this->ends_at !== null && $this->ends_at->isFuture();
    }

    public function isTrial(): bool
    {
        return $this->status === self::TRIAL && $this->isActive();
    }

    public function diasRestantes(): int
    {
        return $this->ends_at ? max(0, (int) ceil(now()->diffInHours($this->ends_at, false) / 24)) : 0;
    }

    public function etiquetaEstado(): string
    {
        return match (true) {
            $this->isTrial() => 'Prueba',
            $this->isActive() => 'Activa',
            $this->status === self::CANCELLED => 'Reemplazada',
            default => 'Vencida',
        };
    }
}
