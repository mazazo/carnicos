<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class AnimalType extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'modalidad',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo'    => 'boolean',
            'modalidad' => 'string',
        ];
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function cutCatalogs(): HasMany
    {
        return $this->hasMany(CutCatalog::class);
    }

    public function cuarteos(): HasMany
    {
        return $this->hasMany(Cuarteo::class);
    }
}
