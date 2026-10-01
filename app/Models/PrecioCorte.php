<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCarniceria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Precio de venta por kg de un corte del catálogo, propio de cada carnicería. */
class PrecioCorte extends Model
{
    use BelongsToCarniceria;

    protected $table = 'precios_corte';

    protected $fillable = ['carniceria_id', 'cut_catalog_id', 'precio_kg', 'user_id'];

    protected function casts(): array
    {
        return ['precio_kg' => 'decimal:2'];
    }

    public function corte(): BelongsTo
    {
        return $this->belongsTo(CutCatalog::class, 'cut_catalog_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
