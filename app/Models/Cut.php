<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use App\Models\CutUserPrice;
    use App\Models\AnimalType;

class Cut extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
            'animal_type_id',
        'nombre',
        'peso',
        'precio_kg',
    ];

    protected function casts(): array
    {
        return [
            'peso' => 'decimal:3',
            'precio_kg' => 'decimal:2',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

        public function animalType(): BelongsTo
        {
            return $this->belongsTo(AnimalType::class);
        }

        public function userPrices(): HasMany
        {
            return $this->hasMany(CutUserPrice::class);
        }
}
