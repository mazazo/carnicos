<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\AnimalType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Animal $animal = null;
    public string $animal_type_id = '';
    public string $fecha = '';

    // Origen / trazabilidad
    public string $frigorifico = '';
    public string $proveedor = '';
    public string $fecha_faena = '';

    // Tipificación SENASA (vacunos)
    public string $categoria = '';
    public string $conformacion = '';
    public string $terminacion = '';
    public string $denticion = '';
    public string $color_grasa = '';
    public string $color_carne = '';
    public string $ph = '';
    public string $temperatura = '';

    // Campos específicos para cajones (aviar)
    public string $cantidad_cajones = '';
    public string $cantidad_aves_por_caja = '';
    public string $kg_por_caja = '';

    // Entrada draft para nueva res (modo creación)
    public string $nuevoPeso = '';
    public string $nuevoPrecio = '';

    /** @var array<int, array{peso_total:string,precio_kg:string}> */
    public array $reses = [];

    private function resolveTypeIdFromQuery(?string $tipo): ?int
    {
        if (!$tipo) {
            return null;
        }

        $normalized = mb_strtolower(trim($tipo));

        $keywords = match ($normalized) {
            'vacuno' => ['vacuno', 'vaca', 'bovino'],
            'porcino' => ['porcino', 'cerdo', 'chancho'],
            'avicola' => ['avicola', 'pollo', 'ave'],
            default => [$normalized],
        };

        $type = AnimalType::query()
            ->where('activo', true)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $index => $keyword) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}('nombre', 'like', '%' . $keyword . '%');
                }
            })
            ->first();

        return $type ? (int) $type->id : null;
    }

    public function mount(?Animal $animal = null): void
    {
        $this->fecha = now()->toDateString();

        if ($animal && (int) $animal->user_id === (int) Auth::id()) {

            $this->animal = $animal;
            $this->animal_type_id = (string) $animal->animal_type_id;
            $this->fecha = $animal->fecha->toDateString();
            $this->frigorifico = (string) ($animal->frigorifico ?? '');
            $this->proveedor = (string) ($animal->proveedor ?? '');
            $this->fecha_faena = $animal->fecha_faena ? $animal->fecha_faena->toDateString() : '';
            $this->cantidad_cajones = (string) ($animal->peso_total ?? '');  // Reutilizar peso_total para cajones
            $this->cantidad_aves_por_caja = (string) ($animal->precio_kg ?? '');  // Reutilizar precio_kg
            $this->kg_por_caja = ''; // Campo adicional (no persistente en edición)
            // Populate component-level SENASA fields for edit
            $this->categoria   = (string) ($animal->categoria ?? '');
            $this->conformacion = $animal->conformacion !== null ? (string) $animal->conformacion : '';
            $this->terminacion  = $animal->terminacion !== null ? (string) $animal->terminacion : '';
            $this->denticion    = $animal->denticion !== null ? (string) $animal->denticion : '';
            $this->color_grasa  = $animal->color_grasa !== null ? (string) $animal->color_grasa : '';
            $this->color_carne  = $animal->color_carne !== null ? (string) $animal->color_carne : '';
            $this->ph           = $animal->ph !== null ? (string) $animal->ph : '';
            $this->temperatura  = $animal->temperatura !== null ? (string) $animal->temperatura : '';

            $this->nuevoPeso = (string) $animal->peso_total;
            $this->nuevoPrecio = $animal->precio_kg !== null ? (string) $animal->precio_kg : '';
            $this->reses = [];
        } else {
            $requestedType = request()->query('tipo');
            $typeQuery = is_string($requestedType) && $requestedType !== '' ? $requestedType : 'vacuno';
            $resolvedTypeId = $this->resolveTypeIdFromQuery($typeQuery);
            if ($resolvedTypeId !== null) {
                $this->animal_type_id = (string) $resolvedTypeId;
            }
        }

        // Para creación nueva: $reses empieza vacío, se llena con addRes()
        // Para edición: $reses vacío, se usa nuevoPeso/nuevoPrecio directamente
    }

    /** @return array{peso_total:string,precio_kg:string} */
    private function emptyRes(): array
    {
        return [
            'peso_total' => '',
            'precio_kg' => '',
        ];
    }

    public function getIsVacunoProperty(): bool
    {
        if ($this->animal_type_id === '') {
            return false;
        }
        $type = AnimalType::find((int) $this->animal_type_id);
        if (!$type) {
            return false;
        }
        $nombre = mb_strtolower($type->nombre);
        return str_contains($nombre, 'vacuno') || str_contains($nombre, 'bovino') || str_contains($nombre, 'vaca');
    }

    public function getModalidadProperty(): ?string
    {
        if ($this->animal_type_id === '') {
            return null;
        }
        $type = AnimalType::find((int) $this->animal_type_id);
        return $type ? $type->modalidad : null;
    }

    public function addRes(): void
    {
        $peso = trim($this->nuevoPeso);
        $precio = trim($this->nuevoPrecio);

        if ($peso === '' || $precio === '') {
            return;
        }

        $this->reses[] = ['peso_total' => $peso, 'precio_kg' => $precio];
        $this->nuevoPeso = '';
        $this->nuevoPrecio = '';
    }

    public function removeRes(int $index): void
    {
        if (count($this->reses) === 0 || $this->animal) {
            return;
        }

        unset($this->reses[$index]);
        $this->reses = array_values($this->reses);
    }

    public function getValorResProperty(): float
    {
        if ($this->modalidad !== 'media_res') {
            return 0;
        }

        return collect($this->reses)->sum(function (array $res): float {
            return ((float) ($res['peso_total'] !== '' ? $res['peso_total'] : 0))
                 * ((float) ($res['precio_kg'] !== '' ? $res['precio_kg'] : 0));
        });
    }

    public function save(): void
    {
        $modalidad = $this->modalidad;
        
        $rules = [
            'animal_type_id' => ['required', 'exists:animal_types,id'],
            'fecha'          => ['required', 'date'],
            'frigorifico'    => ['nullable', 'string', 'max:150'],
            'proveedor'      => ['nullable', 'string', 'max:150'],
            'fecha_faena'    => ['nullable', 'date'],
        ];

        // Reglas condicionales según modalidad
        if ($modalidad === 'media_res') {
            if ($this->animal) {
                // Edición: validar campos draft directamente
                $rules['nuevoPeso']   = ['required', 'numeric', 'gt:0'];
                $rules['nuevoPrecio'] = ['required', 'numeric', 'gt:0'];
            } else {
                $rules['reses']               = ['required', 'array', 'min:1'];
                $rules['reses.*.peso_total']  = ['required', 'numeric', 'gt:0'];
                $rules['reses.*.precio_kg']   = ['required', 'numeric', 'gt:0'];
            }
        } elseif ($modalidad === 'cajon') {
            $rules['cantidad_cajones']       = ['required', 'numeric', 'gt:0'];
            $rules['cantidad_aves_por_caja'] = ['required', 'numeric', 'gt:0'];
            $rules['kg_por_caja']            = ['required', 'numeric', 'gt:0'];
        }

        if ($this->isVacuno) {
            $rules['categoria']    = ['nullable', 'string', 'max:60'];
            $rules['conformacion'] = ['nullable', 'integer', 'between:1,5'];
            $rules['terminacion']  = ['nullable', 'integer', 'between:0,4'];
            $rules['denticion']    = ['nullable', 'integer', 'in:0,2,4,6,8'];
            $rules['color_grasa']  = ['nullable', 'integer', 'between:1,3'];
            $rules['color_carne']  = ['nullable', 'integer', 'between:1,4'];
            $rules['ph']           = ['nullable', 'numeric', 'between:4,8'];
            $rules['temperatura']  = ['nullable', 'numeric', 'between:-10,40'];
        }

        $validated = $this->validate($rules);

        DB::transaction(function () use ($validated, $modalidad) {
            $animal = $this->animal;
            $createdAnimalIds = [];

            // Preparar datos comunes
            $baseAnimalData = [
                'animal_type_id' => (int) $validated['animal_type_id'],
                'fecha'          => $validated['fecha'],
                'frigorifico'    => ($validated['frigorifico'] ?? '') !== '' ? $validated['frigorifico'] : null,
                'proveedor'      => ($validated['proveedor'] ?? '') !== '' ? $validated['proveedor'] : null,
                'fecha_faena'    => ($validated['fecha_faena'] ?? '') !== '' ? $validated['fecha_faena'] : null,
            ];

            // Agregar datos según modalidad
            if ($modalidad === 'media_res') {
                $senasaData = $this->isVacuno ? [
                    'categoria'    => $this->categoria !== '' ? $this->categoria : null,
                    'conformacion' => $this->conformacion !== '' ? (int) $this->conformacion : null,
                    'terminacion'  => $this->terminacion !== '' ? (int) $this->terminacion : null,
                    'denticion'    => $this->denticion !== '' ? (int) $this->denticion : null,
                    'color_grasa'  => $this->color_grasa !== '' ? (int) $this->color_grasa : null,
                    'color_carne'  => $this->color_carne !== '' ? (int) $this->color_carne : null,
                    'ph'           => $this->ph !== '' ? (float) $this->ph : null,
                    'temperatura'  => $this->temperatura !== '' ? (float) $this->temperatura : null,
                ] : [];

                if ($animal) {
                    // Edición: usar draft fields
                    $animalData = array_merge($baseAnimalData, [
                        'peso_total' => (float) $validated['nuevoPeso'],
                        'precio_kg'  => (float) $validated['nuevoPrecio'],
                    ], $senasaData);

                    if ((int) $animal->user_id !== (int) Auth::id()) {
                        abort(403);
                    }
                    $animal->update($animalData);
                    $createdAnimalIds[] = (int) $animal->id;
                } else {
                    foreach ($validated['reses'] as $resData) {
                        $animalData = array_merge($baseAnimalData, [
                            'peso_total' => (float) $resData['peso_total'],
                            'precio_kg'  => (float) $resData['precio_kg'],
                        ], $senasaData);

                        $createdAnimal = Animal::query()->create(array_merge($animalData, [
                            'user_id' => (int) Auth::id(),
                        ]));
                        $createdAnimalIds[] = (int) $createdAnimal->id;
                    }
                }
            } elseif ($modalidad === 'cajon') {
                // Para cajones: cantidad_cajones en peso_total, cantidad_aves en precio_kg
                $animalData = array_merge($baseAnimalData, [
                'peso_total'  => (float) $validated['cantidad_cajones'],
                'precio_kg'   => (float) $validated['cantidad_aves_por_caja'],
                // kg_por_caja se guarda en categoria como string para referencia
                'categoria'   => (string) $validated['kg_por_caja'],
                ]);

                if ($animal) {
                    if ((int) $animal->user_id !== (int) Auth::id()) {
                        abort(403);
                    }
                    $animal->update($animalData);
                    $createdAnimalIds[] = (int) $animal->id;
                } else {
                    $createdAnimal = Animal::query()->create(array_merge($animalData, [
                        'user_id' => (int) Auth::id(),
                    ]));
                    $createdAnimalIds[] = (int) $createdAnimal->id;
                }
            }

            if ($animal) {
                $this->animal = $animal->fresh();
            } elseif ($createdAnimalIds !== []) {
                $this->animal = Animal::query()->find($createdAnimalIds[0]);
            }
        });

        if ($this->animal && $this->animal->exists && $this->animal->wasRecentlyCreated === false && request()->routeIs('animals.edit')) {
            session()->flash('success', 'Animal actualizado correctamente.');
            $this->redirectRoute('animals.show', ['animal' => $this->animal->id], navigate: true);
            return;
        }

        session()->flash('success', $modalidad === 'media_res'
            ? (count($validated['reses'] ?? []) > 1 ? 'Entrega guardada correctamente. Se registraron varias reses.' : 'Res guardada correctamente.')
            : 'Animal guardado correctamente.');
        $this->redirectRoute('animals.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.animals.form', [
            'animal_types' => AnimalType::query()->where('activo', true)->orderBy('nombre')->get(),
        ]);
    }
}
