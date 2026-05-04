<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UsuariosEstado extends Component
{
    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);
    }

    public function render()
    {
        $users = User::query()
            ->with('subscription')
            ->orderBy('name')
            ->orderBy('last_name')
            ->get();

        return view('livewire.admin.usuarios-estado', [
            'users' => $users,
        ]);
    }
}
