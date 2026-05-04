<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class DesposteCorte extends Model
{
    use HasFactory;

    protected $fillable = [
        'desposte_id',
        'cut_catalog_id',
        'nombre',
        'cantidad',
        'peso',
        'precio_kg',
    ];

    protected function casts(): array
    {
        return [
            'cantidad'  => 'integer',
            'peso'      => 'decimal:3',
            'precio_kg' => 'decimal:2',
        ];
    }

    public function desposte(): BelongsTo
    {
        return $this->belongsTo(Desposte::class);
    }

    public function cutCatalog(): BelongsTo
    {
        return $this->belongsTo(CutCatalog::class);
    }

    public function getValorTotalAttribute(): float
    {
        return (float) $this->peso * (float) ($this->precio_kg ?? 0);
    }
}
