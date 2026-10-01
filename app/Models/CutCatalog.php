<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCarniceria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Catálogo de cortes. Los generales (carniceria_id NULL) los ven todas las
 * carnicerías; cada una puede ocultar los que no usa (cortes_ocultos) y sumar
 * cortes propios con sus nombres, que se habilitan con "activo".
 */
class CutCatalog extends Model
{
    use BelongsToCarniceria, HasFactory;

    /** El catálogo general (carniceria_id NULL) lo ven todas las carnicerías. */
    protected static bool $incluyeCompartidos = true;

    /** Corte muscular (asado, nalga…): lo que se vende al mostrador. */
    public const NIVEL_MUSCULO = 'musculo';

    /** Corte primario o pieza grande (Mocho, Parrillero…): agrupa músculos. */
    public const NIVEL_PRIMARIO = 'primario';

    /** Calculado por catalogoPara() para una carnicería; no es columna. */
    public ?bool $habilitado = null;

    protected $fillable = [
        'carniceria_id',
        'user_id',
        'animal_type_id',
        'nombre_canonico',
        'nivel',
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

    public function esPrimario(): bool
    {
        return $this->nivel === self::NIVEL_PRIMARIO;
    }

    /** Músculos que contiene una pieza grande. */
    public function partes(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'cut_catalog_partes', 'primario_id', 'musculo_id');
    }

    public function esPropio(): bool
    {
        return $this->carniceria_id !== null;
    }

    /** Cortes que la carnicería usa: generales activos no ocultos y propios activos. */
    public function scopeHabilitadosPara(Builder $query, int $carniceriaId): void
    {
        $query->where($this->qualifyColumn('activo'), true)
            ->where(fn (Builder $q) => $q->whereNull($this->qualifyColumn('carniceria_id'))->orWhere($this->qualifyColumn('carniceria_id'), $carniceriaId))
            ->whereNotIn($this->qualifyColumn('id'), fn ($sub) => $sub->select('cut_catalog_id')->from('cortes_ocultos')->where('carniceria_id', $carniceriaId));
    }

    /**
     * Catálogo de un tipo de animal visto por la carnicería, con "habilitado"
     * calculado. Incluye los generales activos y todos sus propios.
     *
     * @return \Illuminate\Support\Collection<int, CutCatalog>
     */
    public static function catalogoPara(int $carniceriaId, int $animalTypeId)
    {
        $ocultos = DB::table('cortes_ocultos')->where('carniceria_id', $carniceriaId)->pluck('cut_catalog_id')->flip();

        $cortes = static::query()
            ->withoutGlobalScope('carniceria')
            ->where('animal_type_id', $animalTypeId)
            ->where(fn (Builder $q) => $q->where(fn (Builder $g) => $g->whereNull('carniceria_id')->where('activo', true))->orWhere('carniceria_id', $carniceriaId))
            ->get();

        foreach ($cortes as $corte) {
            $corte->habilitado = $corte->esPropio() ? $corte->activo : ! $ocultos->has($corte->id);
        }

        return $cortes->sortBy(fn (CutCatalog $c) => mb_strtolower($c->nombre_canonico))->values();
    }

    /** Habilita o deshabilita el corte para la carnicería (el general no se toca). */
    public function habilitarPara(int $carniceriaId, bool $habilitar): void
    {
        if ($this->esPropio()) {
            abort_unless((int) $this->carniceria_id === $carniceriaId, 403);
            $this->update(['activo' => $habilitar]);

            return;
        }

        if ($habilitar) {
            DB::table('cortes_ocultos')->where('carniceria_id', $carniceriaId)->where('cut_catalog_id', $this->id)->delete();
        } else {
            DB::table('cortes_ocultos')->insertOrIgnore([
                'carniceria_id' => $carniceriaId,
                'cut_catalog_id' => $this->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Agrega un corte propio. Si ya existe uno con ese nombre (general o
     * propio) se lo habilita en vez de duplicarlo; si ya estaba habilitado, error.
     */
    public static function agregarPropio(int $carniceriaId, int $animalTypeId, string $nombre, ?int $userId, int $cantidadEsperada = 1, string $nivel = self::NIVEL_MUSCULO): self
    {
        $nombre = trim(preg_replace('/\s+/', ' ', $nombre));
        if ($nombre === '') {
            throw ValidationException::withMessages(['nombre' => 'Ingresá el nombre del corte.']);
        }

        $existente = static::catalogoPara($carniceriaId, $animalTypeId)
            ->first(fn (CutCatalog $c) => mb_strtolower($c->nombre_canonico) === mb_strtolower($nombre));

        if ($existente) {
            if ($existente->habilitado) {
                throw ValidationException::withMessages(['nombre' => "\"{$existente->nombre_canonico}\" ya está en tu lista."]);
            }
            $existente->habilitarPara($carniceriaId, true);

            return $existente;
        }

        return static::query()->create([
            'carniceria_id' => $carniceriaId,
            'user_id' => $userId,
            'animal_type_id' => $animalTypeId,
            'nombre_canonico' => $nombre,
            'nivel' => in_array($nivel, [self::NIVEL_MUSCULO, self::NIVEL_PRIMARIO], true) ? $nivel : self::NIVEL_MUSCULO,
            'cantidad_esperada' => max(1, $cantidadEsperada),
            'activo' => true,
        ]);
    }
}
