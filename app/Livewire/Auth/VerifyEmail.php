<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class VerifyEmail extends Component
{
    public bool $resent = false;

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);
            return;
        }

        /** @var User $user */
        $user = Auth::user();
        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail()) {
            $this->redirect(route($user->dashboardRouteName()), navigate: true);
        }
    }

    public function resend(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (! ($user instanceof MustVerifyEmail)) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            $this->redirect(route($user->dashboardRouteName()), navigate: true);
            return;
        }

        $user->sendEmailVerificationNotification();
        $this->resent = true;
    }

    public function logout(): void
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirectRoute('login', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.verify-email');
    }
}
