<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCarniceria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Animal extends Model
{
    use BelongsToCarniceria, HasFactory;

    /** Formato del ingreso: vacuno y cerdo en res o media res; avícola en cajón. */
    public const FORMATO_RES = 'res';

    public const FORMATO_MEDIA_RES = 'media_res';

    public const FORMATO_CAJON = 'cajon';

    /** Pieza grande (corte primario, cut_catalog_id): comprada o salida de un desposte por piezas; se usa en cuarteos. */
    public const FORMATO_PIEZA = 'pieza';

    /** Disponible hasta que se desposta. */
    public const DISPONIBLE = 'disponible';

    public const DESPOSTADA = 'despostada';

    /** Elegida en un desposte pendiente: ya no está disponible para otro. */
    public const EN_DESPOSTE = 'en_desposte';

    protected $fillable = [
        'cut_catalog_id',
        'desposte_origen_id',
        'carniceria_id',
        'user_id',
        'animal_type_id',
        'formato',
        'cantidad',
        'estado',
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
            'cantidad' => 'integer',
            'peso_total' => 'decimal:3',
            'precio_kg' => 'decimal:2',
            'fecha' => 'date',
            'fecha_faena' => 'date',
            'conformacion' => 'integer',
            'terminacion' => 'integer',
            'denticion' => 'integer',
            'color_grasa' => 'integer',
            'color_carne' => 'integer',
            'ph' => 'decimal:2',
            'temperatura' => 'decimal:2',
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

    /** Despostes en los que entró (uno o varios, con otras medias). */
    public function despostesProduccion(): BelongsToMany
    {
        return $this->belongsToMany(Desposte::class, 'desposte_animales')->withPivot(['peso', 'precio_kg'])->withTimestamps();
    }

    public function scopeDisponibles(Builder $query): void
    {
        $query->where('estado', self::DISPONIBLE);
    }

    /** Formatos de ingreso según la modalidad del tipo de animal. @return list<string> */
    public static function formatosPara(?AnimalType $tipo): array
    {
        return $tipo?->modalidad === 'cajon' ? [self::FORMATO_CAJON] : [self::FORMATO_RES, self::FORMATO_MEDIA_RES];
    }

    public function etiquetaFormato(): string
    {
        return match ($this->formato) {
            self::FORMATO_RES => 'Res',
            self::FORMATO_MEDIA_RES => 'Media res',
            self::FORMATO_CAJON => 'Cajón',
            self::FORMATO_PIEZA => $this->cutCatalog?->nombre_canonico ?? 'Pieza',
            default => (string) $this->formato,
        };
    }

    public function esPieza(): bool
    {
        return $this->formato === self::FORMATO_PIEZA;
    }

    /** Corte primario de una pieza grande. */
    public function cutCatalog(): BelongsTo
    {
        return $this->belongsTo(CutCatalog::class)->withoutGlobalScopes();
    }

    /** Desposte por piezas del que salió esta pieza. */
    public function desposteOrigen(): BelongsTo
    {
        return $this->belongsTo(Desposte::class, 'desposte_origen_id');
    }

    /** Medias, reses y cajones (lo que se desposta por músculo o por piezas); sin piezas grandes. */
    public function scopeSinPiezas(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->where('formato', '!=', self::FORMATO_PIEZA)->orWhereNull('formato'));
    }

    public function scopePiezas(Builder $query): void
    {
        $query->where('formato', self::FORMATO_PIEZA);
    }

    /** Días desde que ingresó (0 = hoy). Referencia de cuánto hace que está en la carnicería. */
    public function diasEnStock(): int
    {
        return $this->fecha ? max(0, (int) $this->fecha->copy()->startOfDay()->diffInDays(today())) : 0;
    }

    /** Sigue en la carnicería (disponible o en un desposte pendiente). */
    public function enStock(): bool
    {
        return in_array($this->estado, [self::DISPONIBLE, self::EN_DESPOSTE], true);
    }

    public function costoTotal(): float
    {
        return round((float) $this->peso_total * (float) $this->precio_kg, 2);
    }

    public function cuarteos(): HasMany
    {
        return $this->hasMany(Cuarteo::class);
    }
}
