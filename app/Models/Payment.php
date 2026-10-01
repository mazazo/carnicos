<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pago de una carnicería por un período de un plan. Manual (transferencia o
 * efectivo, registrado o aprobado por el administrador) o por Mercado Pago.
 */
class Payment extends Model
{
    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const FAILED = 'failed';

    public const REFUNDED = 'refunded';

    protected $fillable = [
        'carniceria_id',
        'plan_id',
        'meses',
        'subscription_id',
        'user_id',
        'amount',
        'currency',
        'status',
        'method',
        'proveedor',
        'proveedor_pago_id',
        'proveedor_preferencia_id',
        'proveedor_respuesta',
        'reference',
        'notes',
        'registrado_por',
        'paid_at',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meses' => 'integer',
        'proveedor_respuesta' => 'array',
        'paid_at' => 'datetime',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
    ];

    public function carniceria(): BelongsTo
    {
        return $this->belongsTo(Carniceria::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    public function etiquetaEstado(): string
    {
        return match ($this->status) {
            self::PAID => 'Pagado',
            self::PENDING => 'Pendiente',
            self::FAILED => 'Rechazado',
            self::REFUNDED => 'Devuelto',
            default => ucfirst((string) $this->status),
        };
    }
}
