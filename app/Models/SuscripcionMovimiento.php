<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Historial de la suscripción de una carnicería (quién hizo qué y cuándo). */
class SuscripcionMovimiento extends Model
{
    protected $table = 'suscripcion_movimientos';

    protected $fillable = ['carniceria_id', 'subscription_id', 'tipo', 'detalle', 'user_id'];

    public function carniceria(): BelongsTo
    {
        return $this->belongsTo(Carniceria::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
