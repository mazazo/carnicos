<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCarniceria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Producción (desposte) de una o varias medias de la carnicería. Las medias
 * están en desposte_animales con el peso y precio que tenían al despostarse;
 * animal_id queda solo para los despostes de una media cargados antes.
 */
class Desposte extends Model
{
    use BelongsToCarniceria, HasFactory;

    /** Se está cargando desde la app: tiene pesadas, todavía no cortes. */
    public const PENDIENTE = 'pendiente';

    public const TERMINADO = 'terminado';

    /** Por músculo: media/res → cortes musculares (el de siempre, el de la app). */
    public const MODO_MUSCULO = 'musculo';

    /** Por piezas grandes: media/res → cortes primarios, que quedan como piezas disponibles. */
    public const MODO_PRIMARIO = 'primario';

    /** Cuarteo: pieza grande → sus músculos. */
    public const MODO_CUARTEO = 'cuarteo';

    public const MODOS = [
        self::MODO_MUSCULO => 'Por músculo',
        self::MODO_PRIMARIO => 'Por piezas grandes',
        self::MODO_CUARTEO => 'Cuarteo',
    ];

    protected $fillable = [
        'carniceria_id',
        'animal_type_id',
        'estado',
        'modo',
        'animal_id',
        'user_id',
        'despostador_nombre',
        'fecha_desposte',
        'observaciones',
        'kg_medias',
        'kg_cortes',
        'kg_merma',
        'rinde_pct',
        'costo',
        'venta',
        'ganancia',
        'margen_pct',
    ];

    protected function casts(): array
    {
        return [
            'fecha_desposte' => 'date',
            'kg_medias' => 'float',
            'kg_cortes' => 'float',
            'kg_merma' => 'float',
            'rinde_pct' => 'float',
            'costo' => 'float',
            'venta' => 'float',
            'ganancia' => 'float',
            'margen_pct' => 'float',
        ];
    }

    /**
     * Guarda el cálculo del momento en que se termina (kg, rinde, costo con el
     * precio de ingreso de las medias, venta con el precio vigente de cada
     * corte, ganancia y margen sobre el costo). No cambia si después cambian los precios.
     */
    public function guardarCalculo(): void
    {
        $this->load(['animales', 'cortes']);
        $kgMedias = $this->peso_medias;
        $kgCortes = $this->peso_total_cortes;
        $costo = $this->costo_total;
        $venta = round($this->valor_total, 2);

        $this->update([
            'kg_medias' => $kgMedias,
            'kg_cortes' => $kgCortes,
            'kg_merma' => max(0, $kgMedias - $kgCortes),
            'rinde_pct' => $kgMedias > 0 ? round($kgCortes / $kgMedias * 100, 2) : null,
            'costo' => $costo,
            'venta' => $venta,
            'ganancia' => round($venta - $costo, 2),
            'margen_pct' => $costo > 0 ? round(($venta - $costo) / $costo * 100, 2) : null,
        ]);
    }

    /** Terminado y con el cálculo guardado. */
    public function tieneCalculo(): bool
    {
        return $this->estado === self::TERMINADO && $this->costo !== null;
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }

    /** Medias despostadas, con su peso y precio de ingreso. */
    public function animales(): BelongsToMany
    {
        return $this->belongsToMany(Animal::class, 'desposte_animales')->withPivot(['peso', 'precio_kg'])->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cortes(): HasMany
    {
        return $this->hasMany(DesposteCorte::class);
    }

    public function pesadas(): HasMany
    {
        return $this->hasMany(DespostePesada::class);
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::PENDIENTE;
    }

    public function getPesoTotalCortesAttribute(): float
    {
        return (float) $this->cortes->sum('peso');
    }

    public function getValorTotalAttribute(): float
    {
        return $this->cortes->sum(fn ($c) => (float) $c->peso * (float) ($c->precio_kg ?? 0));
    }

    /** Costo de las medias: kg × precio de ingreso de cada una. */
    public function getCostoTotalAttribute(): float
    {
        return round($this->animales->sum(fn (Animal $a) => (float) $a->pivot->peso * (float) $a->pivot->precio_kg), 2);
    }

    public function getPesoMediasAttribute(): float
    {
        return (float) $this->animales->sum(fn (Animal $a) => (float) $a->pivot->peso);
    }
}
