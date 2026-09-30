<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CutUserPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cut_id',
        'precio_kg',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'precio_kg' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cut(): BelongsTo
    {
        return $this->belongsTo(Cut::class);
    }
}
