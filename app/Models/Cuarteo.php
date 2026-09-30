<?php

namespace App\Models;

use App\Models\CuarteoParte;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuarteo extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'animal_type_id',
        'user_id',
        'fecha_cuarteo',
        'subtipo',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cuarteo' => 'date',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }

    public function partes(): HasMany
    {
        return $this->hasMany(CuarteoParte::class);
    }

    public function getPesoTotalPartesAttribute(): float
    {
        return (float) $this->partes->sum('peso');
    }

    public function getValorTotalAttribute(): float
    {
        return $this->partes->sum(fn ($parte) => (float) $parte->peso * (float) ($parte->precio_kg ?? 0));
    }
}
