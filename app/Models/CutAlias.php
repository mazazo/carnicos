<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class CutAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'cut_catalog_id',
        'alias',
    ];

    public function cutCatalog(): BelongsTo
    {
        return $this->belongsTo(CutCatalog::class);
    }
}
