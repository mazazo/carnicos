<?php

namespace App\Livewire\Billing;

use App\Support\Billing\PlanCatalog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Plans extends Component
{
    public function planActual(): string
    {
        return Auth::user()?->plan ?? 'starter';
    }

    public function render()
    {
        return view('livewire.billing.plans', [
            'planes'     => PlanCatalog::all(),
            'planActual' => $this->planActual(),
            'user'       => Auth::user(),
        ]);
    }
}
