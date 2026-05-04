<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';
    public string $last_name = '';
    public string $email = '';
    public string $movil = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $isLocked = false;
    public int $retryAfter = 0;
    public bool $showWarning = false;
    public int $attemptsRemaining = 15;

    private const WARNING_ATTEMPTS = 5;
    private const MAX_ATTEMPTS = 15;
    private const DECAY_SECONDS = 600;

    public function mount(): void
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            $this->redirect(route($user->dashboardRouteName()), navigate: true);
        }

        $this->syncLockState();
    }

    public function register(): void
    {
        if ($this->hasTooManyAttempts()) {
            $this->addError('email', $this->lockoutMessage());
            return;
        }

        try {
            $this->normalizarDatos();

            $data = $this->validate([
                'name' => ['required', 'string', 'min:2', 'max:60', 'regex:/^[\pL\s\-\']+$/u'],
                'last_name' => ['nullable', 'string', 'min:2', 'max:60', 'regex:/^[\pL\s\-\']+$/u'],
                'email' => ['required', 'string', 'email:rfc', 'max:150', 'unique:users,email'],
                'movil' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/', 'unique:users,movil'],
                'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            ], [
                'name.regex' => 'El nombre solo puede contener letras, espacios, apostrofe y guion.',
                'last_name.regex' => 'El apellido solo puede contener letras, espacios, apostrofe y guion.',
                'last_name.min' => 'Si ingresas apellido, debe tener al menos 2 caracteres.',
                'movil.regex' => 'El movil debe tener entre 8 y 15 digitos (opcional + al inicio).',
            ]);
        } catch (ValidationException $exception) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);
            $this->syncLockState();
            throw $exception;
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'last_name' => $data['last_name'] ?: null,
            'email' => $data['email'],
            'movil' => $data['movil'],
            'password' => $data['password'],
        ]);

        RateLimiter::clear($this->throttleKey());
        $this->syncLockState();

        Auth::login($user);
        request()->session()->regenerate();
        $user->sendEmailVerificationNotification();

        $this->redirectRoute('verification.notice', navigate: true);
    }

    private function normalizarDatos(): void
    {
        $this->name = trim(preg_replace('/\s+/', ' ', $this->name));
        $this->last_name = trim((string) preg_replace('/\s+/', ' ', $this->last_name));
        $this->email = mb_strtolower(trim($this->email));
        $this->movil = preg_replace('/\s+/', '', trim($this->movil));
    }

    private function throttleKey(): string
    {
        return 'register-ip:' . request()->ip();
    }

    private function hasTooManyAttempts(): bool
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return false;
        }

        $this->syncLockState();

        return true;
    }

    private function syncLockState(): void
    {
        $attempts = RateLimiter::attempts($this->throttleKey());
        $this->isLocked = RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS);
        $this->retryAfter = $this->isLocked ? RateLimiter::availableIn($this->throttleKey()) : 0;
        $this->attemptsRemaining = max(0, self::MAX_ATTEMPTS - $attempts);
        $this->showWarning = ! $this->isLocked && $attempts >= self::WARNING_ATTEMPTS;
    }

    private function lockoutMessage(): string
    {
        return 'Superaste el limite de intentos desde esta IP. Intenta nuevamente en ' . ceil($this->retryAfter / 60) . ' minuto(s) o suscribite para continuar.';
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
