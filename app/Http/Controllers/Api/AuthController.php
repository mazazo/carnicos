<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegistrarCarniceria;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AccesoApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Ingreso, registro y cuenta para la app. El token (Sanctum) se guarda en el
 * teléfono; "acceso" dice si su plan incluye la app o por qué está bloqueada.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'dispositivo' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', mb_strtolower(trim($datos['email'])))->first();

        if (! $user || ! Hash::check($datos['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Credenciales inválidas.']);
        }

        return response()->json($this->sesion($user, $datos['dispositivo'] ?? null));
    }

    public function register(Request $request, RegistrarCarniceria $registrar): JsonResponse
    {
        $datos = RegistrarCarniceria::normalizar($request->all());
        $validados = Validator::make($datos, RegistrarCarniceria::reglas(), RegistrarCarniceria::mensajes())->validate();

        $user = $registrar->handle($validados);
        $user->sendEmailVerificationNotification();

        return response()->json($this->sesion($user, $request->input('dispositivo')), 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(self::cuenta($request->user()));
    }

    /** @return array<string, mixed> */
    private function sesion(User $user, ?string $dispositivo): array
    {
        $token = $user->createToken(trim((string) $dispositivo) ?: 'app')->plainTextToken;

        return ['token' => $token] + self::cuenta($user);
    }

    /** @return array<string, mixed> */
    public static function cuenta(User $user): array
    {
        $carniceria = $user->carniceria;
        $vigente = $carniceria?->suscripcionVigente();

        return [
            'usuario' => [
                'id' => $user->id,
                'nombre' => $user->full_name,
                'email' => $user->email,
                'rol' => $user->rol,
                'es_dueno' => $user->esDueno(),
                'permisos' => collect(array_keys(User::PERMISOS))->filter(fn (string $p) => $user->puede($p))->values()->all(),
                'email_verificado' => $user->hasVerifiedEmail(),
            ],
            'carniceria' => $carniceria ? ['id' => $carniceria->id, 'nombre' => $carniceria->nombre] : null,
            'plan' => $vigente ? [
                'codigo' => $vigente->planModel?->codigo,
                'nombre' => $vigente->planModel?->nombre,
                'prueba' => $vigente->isTrial(),
                'vence' => $vigente->ends_at->toIso8601String(),
                'dias_restantes' => $vigente->diasRestantes(),
            ] : null,
            'acceso' => AccesoApp::para($user)->toArray(),
        ];
    }
}
