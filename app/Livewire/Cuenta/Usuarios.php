<?php

namespace App\Livewire\Cuenta;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Usuarios de la carnicería. Solo el dueño los administra, y puede crear
 * hasta el máximo de su plan (Plan 1: no crea; Plan 2: 2 en total; Plan 3: 4).
 * A cada empleado le elige qué puede usar: ingresos, producciones, cortes y la app.
 */
#[Layout('layouts.app')]
class Usuarios extends Component
{
    public bool $mostrarFormulario = false;

    public string $name = '';

    public string $last_name = '';

    public string $email = '';

    public string $movil = '';

    public string $password = '';

    /** @var array<int, string> permisos del empleado nuevo */
    public array $permisos = ['ingresos', 'producciones', 'cortes', 'app'];

    /** Empleado al que se le están editando los permisos. */
    public ?int $editandoId = null;

    /** @var array<int, string> */
    public array $permisosEditando = [];

    public function nuevo(): void
    {
        $this->asegurarDueno();
        abort_unless($this->puedeCrear(), 403);
        $this->reset('name', 'last_name', 'email', 'movil', 'password', 'permisos');
        $this->resetValidation();
        $this->mostrarFormulario = true;
    }

    public function crear(): void
    {
        $dueno = $this->asegurarDueno();

        if (! $this->puedeCrear()) {
            throw ValidationException::withMessages(['email' => 'Tu plan no permite más usuarios. Pasate a un plan con más usuarios.']);
        }

        $this->email = mb_strtolower(trim($this->email));
        $this->movil = (string) preg_replace('/\s+/', '', trim($this->movil));

        $data = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'last_name' => ['nullable', 'string', 'max:60'],
            'email' => ['required', 'email:rfc', 'max:150', 'unique:users,email'],
            'movil' => ['required', 'regex:/^\+?[0-9]{8,15}$/', 'unique:users,movil'],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()],
            'permisos' => ['array'],
            'permisos.*' => ['string', 'in:'.implode(',', array_keys(User::PERMISOS))],
        ], ['movil.regex' => 'El móvil debe tener entre 8 y 15 dígitos.']);

        $usuario = User::query()->create($data + [
            'carniceria_id' => $dueno->carniceria_id,
            'rol' => User::ROL_EMPLEADO,
            'permisos' => array_values(array_unique($this->permisos)),
        ]);
        $usuario->forceFill(['email_verified_at' => now()])->save(); // lo da de alta el dueño

        $this->mostrarFormulario = false;
        session()->flash('success', "Usuario {$usuario->email} creado.");
    }

    public function editarPermisos(int $id): void
    {
        $empleado = $this->empleado($id);
        $this->editandoId = $empleado->id;
        $this->permisosEditando = $empleado->permisos ?? [];
    }

    public function guardarPermisos(): void
    {
        $empleado = $this->empleado((int) $this->editandoId);
        $this->validate(['permisosEditando.*' => ['string', 'in:'.implode(',', array_keys(User::PERMISOS))]]);

        $empleado->update(['permisos' => array_values(array_unique($this->permisosEditando))]);
        if (! $empleado->puede('app')) {
            $empleado->tokens()->delete(); // sin permiso de app: se cierra su sesión en el celular
        }

        $this->reset('editandoId', 'permisosEditando');
        session()->flash('success', "Permisos de {$empleado->full_name} guardados.");
    }

    public function cancelarPermisos(): void
    {
        $this->reset('editandoId', 'permisosEditando');
    }

    /** Un empleado de la carnicería del dueño (al dueño no se le editan permisos). */
    private function empleado(int $id): User
    {
        $dueno = $this->asegurarDueno();

        return User::query()
            ->where('carniceria_id', $dueno->carniceria_id)
            ->where('rol', User::ROL_EMPLEADO)
            ->findOrFail($id);
    }

    public function eliminar(int $id): void
    {
        $dueno = $this->asegurarDueno();
        $usuario = User::query()
            ->where('carniceria_id', $dueno->carniceria_id)
            ->where('rol', User::ROL_EMPLEADO)
            ->findOrFail($id);
        $usuario->delete();

        session()->flash('success', "Usuario {$usuario->email} eliminado.");
    }

    private function asegurarDueno(): User
    {
        $user = Auth::user();
        abort_unless($user->carniceria_id && $user->esDueno(), 403);

        return $user;
    }

    private function puedeCrear(): bool
    {
        $user = Auth::user();
        $plan = $user->planVigente();

        return $plan !== null
            && $plan->permiteUsuarios(User::query()->where('carniceria_id', $user->carniceria_id)->count());
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.cuenta.usuarios', [
            // 'dueno' queda antes que 'empleado' por orden alfabético.
            'usuarios' => User::query()->where('carniceria_id', $user->carniceria_id)->orderBy('rol')->orderBy('name')->get(),
            'plan' => $user->planVigente(),
            'esDueno' => $user->esDueno(),
            'puedeCrear' => $user->esDueno() && $this->puedeCrear(),
            'listaPermisos' => User::PERMISOS,
        ]);
    }
}
