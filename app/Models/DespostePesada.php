<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Una pesada de un corte en un desposte pendiente; al terminarlo se suman por corte. */
class DespostePesada extends Model
{
    protected $fillable = [
        'desposte_id',
        'cut_catalog_id',
        'peso',
        'user_id',
    ];

    public function desposte(): BelongsTo
    {
        return $this->belongsTo(Desposte::class);
    }

    public function cutCatalog(): BelongsTo
    {
        return $this->belongsTo(CutCatalog::class);
    }
}
