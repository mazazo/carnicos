<?php

namespace App\Actions;

use App\Models\Carniceria;
use App\Models\User;
use App\Services\Suscripciones;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * Alta de un cliente (web y app): crea la carnicería, su dueño y la prueba
 * gratis (días configurables, con acceso de Plan 1).
 */
class RegistrarCarniceria
{
    public function __construct(private Suscripciones $suscripciones) {}

    /** @return array<string, mixed> */
    public static function reglas(): array
    {
        return [
            'carniceria' => ['required', 'string', 'min:2', 'max:150'],
            'name' => ['required', 'string', 'min:2', 'max:60', 'regex:/^[\pL\s\-\']+$/u'],
            'last_name' => ['nullable', 'string', 'min:2', 'max:60', 'regex:/^[\pL\s\-\']+$/u'],
            'email' => ['required', 'string', 'email:rfc', 'max:150', 'unique:users,email'],
            'movil' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/', 'unique:users,movil'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ];
    }

    /** @return array<string, string> */
    public static function mensajes(): array
    {
        return [
            'name.regex' => 'El nombre solo puede contener letras, espacios, apostrofe y guion.',
            'last_name.regex' => 'El apellido solo puede contener letras, espacios, apostrofe y guion.',
            'last_name.min' => 'Si ingresas apellido, debe tener al menos 2 caracteres.',
            'movil.regex' => 'El movil debe tener entre 8 y 15 digitos (opcional + al inicio).',
        ];
    }

    /**
     * Mismos ajustes que el formulario web: espacios de más, email en minúsculas, móvil sin espacios.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public static function normalizar(array $datos): array
    {
        return array_merge($datos, [
            'carniceria' => trim((string) preg_replace('/\s+/', ' ', (string) ($datos['carniceria'] ?? ''))),
            'name' => trim((string) preg_replace('/\s+/', ' ', (string) ($datos['name'] ?? ''))),
            'last_name' => trim((string) preg_replace('/\s+/', ' ', (string) ($datos['last_name'] ?? ''))),
            'email' => mb_strtolower(trim((string) ($datos['email'] ?? ''))),
            'movil' => (string) preg_replace('/\s+/', '', trim((string) ($datos['movil'] ?? ''))),
        ]);
    }

    /** @param  array<string, mixed>  $data  datos ya validados */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $carniceria = Carniceria::query()->create([
                'nombre' => $data['carniceria'],
                'email' => $data['email'],
                'telefono' => $data['movil'],
            ]);

            $dueno = User::query()->create([
                'carniceria_id' => $carniceria->id,
                'rol' => User::ROL_DUENO,
                'name' => $data['name'],
                'last_name' => ($data['last_name'] ?? '') ?: null,
                'email' => $data['email'],
                'movil' => $data['movil'],
                'password' => $data['password'],
            ]);

            $this->suscripciones->iniciarPrueba($carniceria, $dueno);

            return $dueno;
        });
    }
}
