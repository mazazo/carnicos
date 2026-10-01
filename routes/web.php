<?php

use App\Livewire\Animals\Form as AnimalsForm;
use App\Livewire\Animals\Index as AnimalsIndex;
use App\Livewire\Animals\Show as AnimalsShow;
use App\Livewire\Auth\Login as AuthLogin;
use App\Livewire\Auth\Register as AuthRegister;
use App\Livewire\Auth\VerifyEmail as AuthVerifyEmail;
use App\Livewire\Billing\Checkout as BillingCheckout;
use App\Models\AnimalType;
use App\Models\CutReferenceAverage;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Livewire\Billing\Plans as BillingPlans;
use App\Livewire\Cuts\Form as CutsForm;
use App\Livewire\Cuts\Index as CutsIndex;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Admin\ConfigIndex as AdminConfigIndex;
use App\Livewire\Admin\CorteTipoAudit as AdminCorteTipoAudit;
use App\Livewire\Admin\Logs as AdminLogs;
use App\Livewire\Admin\Carnicerias as AdminCarnicerias;
use App\Livewire\Admin\CarniceriaShow as AdminCarniceriaShow;
use App\Livewire\Admin\Planes as AdminPlanes;
use App\Livewire\Admin\Pagos as AdminPagos;
use App\Livewire\Billing\Retorno as BillingRetorno;
use App\Livewire\Cuenta\TiposAnimal as CuentaTiposAnimal;
use App\Livewire\Cuenta\Usuarios as CuentaUsuarios;
use App\Http\Controllers\MercadoPagoWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        /** @var User $user */
        $user = Auth::user();

        return redirect()->route($user->dashboardRouteName());
    }

    return redirect()->route('register');
})->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', AuthLogin::class)->name('login');
    Route::get('/register', AuthRegister::class)->name('register');
    Route::get('/crear-usuario', AuthRegister::class)->name('register.create-user');
});

// Email verification
Route::get('/email/verify', AuthVerifyEmail::class)
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    /** @var User $user */
    $user = $request->user();

    return redirect()->route($user->dashboardRouteName());
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');

Route::post('/email/verification-notification', function () {
    request()->user()->sendEmailVerificationNotification();
    return back()->with('resent', true);
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('register');
})->middleware('auth')->name('logout');

// Planes y pago: siempre accesibles (aunque la prueba o el plan hayan vencido).
Route::middleware('auth')->group(function () {
    Route::get('/billing/planes', BillingPlans::class)->name('billing.plans');
    Route::get('/billing/pasarela/{codigo}', BillingCheckout::class)->name('billing.checkout');
    Route::get('/billing/retorno/{payment}', BillingRetorno::class)->whereNumber('payment')->name('billing.retorno');
});

// Avisos de Mercado Pago (sin sesión; se valida la firma y se consulta el pago).
Route::post('/webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.mercadopago');

// Todo lo demás exige prueba o plan vigente (bloqueo total).
Route::middleware(['auth', 'verified', 'suscripcion'])->group(function () {
    Route::get('/cuenta/tipos-de-animal', CuentaTiposAnimal::class)->name('cuenta.tipos-animal');
    Route::get('/cuenta/usuarios', CuentaUsuarios::class)->name('cuenta.usuarios');

    Route::get('/dashboard', function () {
        /** @var User $user */
        $user = request()->user();

        return redirect()->route($user->dashboardRouteName());
    })->name('dashboard');
    Route::get('/dashboard/enterprise', DashboardIndex::class)
        ->defaults('variant', 'enterprise')
        ->name('dashboard.enterprise');
    Route::get('/dashboard/pro', DashboardIndex::class)
        ->defaults('variant', 'pro')
        ->name('dashboard.pro');

    Route::get('/animals', AnimalsIndex::class)->name('animals.index');
    Route::get('/animals/create', AnimalsForm::class)->name('animals.create');
    Route::get('/animals/{animal}/edit', AnimalsForm::class)->name('animals.edit');
    Route::get('/animals/{animal}', AnimalsShow::class)->name('animals.show');

    Route::get('/cuts', CutsIndex::class)->name('cuts.index');
    Route::get('/cuts/create', CutsForm::class)->name('cuts.create');
    Route::get('/cuts/{cut}/edit', CutsForm::class)->name('cuts.edit');

    Route::get('/admin/config', AdminConfigIndex::class)->name('admin.config.index');
    Route::redirect('/admin/config/usuarios', '/admin/config/carnicerias')->name('admin.config.usuarios');
    Route::get('/admin/config/cortes-por-tipo', AdminCorteTipoAudit::class)->name('admin.config.cortes');
    Route::get('/admin/config/logs', AdminLogs::class)->name('admin.config.logs');
    Route::get('/admin/config/carnicerias', AdminCarnicerias::class)->name('admin.carnicerias.index');
    Route::get('/admin/config/carnicerias/{carniceria}', AdminCarniceriaShow::class)->whereNumber('carniceria')->name('admin.carnicerias.show');
    Route::get('/admin/config/planes', AdminPlanes::class)->name('admin.planes');
    Route::get('/admin/config/pagos', AdminPagos::class)->name('admin.pagos');

    Route::post('/admin/cuts/visibility', function (Request $request) {
        $user = $request->user();
        abort_unless($user && $user->isAdmin(), 403);

        $validated = $request->validate([
            'category' => ['required', 'string', 'max:50'],
            'cut' => ['required', 'string', 'max:120'],
            'active' => ['required', 'boolean'],
        ]);

        $visibility = Cache::get('landing_cut_visibility', []);
        $category = $validated['category'];
        $cut = $validated['cut'];
        $active = (bool) $validated['active'];

        if (! isset($visibility[$category]) || ! is_array($visibility[$category])) {
            $visibility[$category] = [];
        }

        $visibility[$category][$cut] = $active;
        Cache::forever('landing_cut_visibility', $visibility);

        return response()->json([
            'ok' => true,
            'category' => $category,
            'cut' => $cut,
            'active' => $active,
        ]);
    })->name('admin.cuts.visibility');
});
