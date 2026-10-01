<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Precio de un plan por período (el "tiempo" que se contrata: 1, 3, 6, 12 meses).
 */
class PlanPrecio extends Model
{
    protected $table = 'plan_precios';

    protected $fillable = [
        'plan_id',
        'meses',
        'precio',
        'moneda',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'meses' => 'integer',
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** "1 mes", "3 meses", "12 meses". */
    public function etiquetaPeriodo(): string
    {
        return $this->meses === 1 ? 'mes' : "{$this->meses} meses";
    }
}
