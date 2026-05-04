<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use App\Models\User;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;
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

    public function login(): void
    {
        if ($this->hasTooManyAttempts()) {
            $this->addError('email', $this->lockoutMessage());
            return;
        }

        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);
            $this->syncLockState();
            $this->addError('email', 'Credenciales inválidas.');
            return;
        }

        RateLimiter::clear($this->throttleKey());
        $this->syncLockState();

        request()->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        $this->redirectIntended(default: route($user->dashboardRouteName()), navigate: true);
    }

    private function throttleKey(): string
    {
        return 'login-ip:' . request()->ip();
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
        return view('livewire.auth.login');
    }
}
