<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Desposte extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'user_id',
        'despostador_nombre',
        'fecha_desposte',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_desposte' => 'date',
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

    public function cortes(): HasMany
    {
        return $this->hasMany(DesposteCorte::class);
    }

    public function getPesoTotalCortesAttribute(): float
    {
        return (float) $this->cortes->sum('peso');
    }

    public function getValorTotalAttribute(): float
    {
        return $this->cortes->sum(fn ($c) => (float) $c->peso * (float) ($c->precio_kg ?? 0));
    }
}
