<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCarniceria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    use BelongsToCarniceria, HasFactory;

    protected $fillable = [
        'carniceria_id',
        'user_id',
        'animal_type_id',
        'peso_total',
        'precio_kg',
        'fecha',
        'frigorifico',
        'proveedor',
        'fecha_faena',
        'categoria',
        'conformacion',
        'terminacion',
        'denticion',
        'color_grasa',
        'color_carne',
        'ph',
        'temperatura',
    ];

    protected function casts(): array
    {
        return [
            'peso_total'   => 'decimal:3',
            'precio_kg'    => 'decimal:2',
            'fecha'        => 'date',
            'fecha_faena'  => 'date',
            'conformacion' => 'integer',
            'terminacion'  => 'integer',
            'denticion'    => 'integer',
            'color_grasa'  => 'integer',
            'color_carne'  => 'integer',
            'ph'           => 'decimal:2',
            'temperatura'  => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }

    public function cuts(): HasMany
    {
        return $this->hasMany(Cut::class);
    }

    public function despostes(): HasMany
    {
        return $this->hasMany(Desposte::class);
    }

    public function cuarteos(): HasMany
    {
        return $this->hasMany(Cuarteo::class);
    }
}
