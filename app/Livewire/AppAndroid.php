<?php

namespace App\Livewire;

use App\Http\Controllers\AppAndroidController;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

/** Descarga de la app Android y pasos para instalarla (Plan Completo con permiso de app). */
class AppAndroid extends Component
{
    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->puedeDescargarApp(), 403, 'La app Android está incluida en el Plan Completo y necesita el permiso de app.');
    }

    public function render()
    {
        $disco = Storage::disk('local');
        $hay = $disco->exists(AppAndroidController::ARCHIVO);

        return view('livewire.app-android', [
            'hayApk' => $hay,
            'tamanoMb' => $hay ? round($disco->size(AppAndroidController::ARCHIVO) / 1048576, 1) : null,
            'actualizada' => $hay ? Carbon::createFromTimestamp($disco->lastModified(AppAndroidController::ARCHIVO)) : null,
            'esAdmin' => Auth::user()->isAdmin(),
            'ruta' => $disco->path(AppAndroidController::ARCHIVO),
        ]);
    }
}
