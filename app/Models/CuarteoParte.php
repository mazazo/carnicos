<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuarteoParte extends Model
{
    use HasFactory;

    protected $table = 'cuarteo_partes';

    protected $fillable = [
        'cuarteo_id',
        'cut_catalog_id',
        'nombre_parte',
        'cantidad',
        'peso',
        'precio_kg',
        'porcentaje',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'peso' => 'decimal:3',
            'precio_kg' => 'decimal:2',
            'porcentaje' => 'decimal:2',
        ];
    }

    public function cuarteo(): BelongsTo
    {
        return $this->belongsTo(Cuarteo::class);
    }

    public function cutCatalog(): BelongsTo
    {
        return $this->belongsTo(CutCatalog::class);
    }
}
