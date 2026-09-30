<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class CutCatalog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'animal_type_id',
        'nombre_canonico',
        'cantidad_esperada',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_esperada' => 'integer',
            'activo'            => 'boolean',
        ];
    }

    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(CutAlias::class);
    }

    public function desposteCortes(): HasMany
    {
        return $this->hasMany(DesposteCorte::class);
    }

    public function cuarteoPartes(): HasMany
    {
        return $this->hasMany(CuarteoParte::class);
    }

    public function referenceAverages(): HasMany
    {
        return $this->hasMany(CutReferenceAverage::class);
    }
}
