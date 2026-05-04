<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class CutReferenceAverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_type_id',
        'cut_catalog_id',
        'peso_referencia',
        'kg_promedio',
    ];

    protected function casts(): array
    {
        return [
            'peso_referencia' => 'decimal:3',
            'kg_promedio'     => 'decimal:3',
        ];
    }

    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }

    public function cutCatalog(): BelongsTo
    {
        return $this->belongsTo(CutCatalog::class);
    }
}
