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
use App\Livewire\Admin\UsuariosEstado as AdminUsuariosEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // display weight options and default per animal type
    $config = [
        'vacuno'  => ['key' => 'vacuno',  'name' => 'Vacuno',  'title' => 'Listado de cortes vacunos',  'displayWeights' => [90, 100, 110, 118, 120, 130], 'defaultWeight' => 118],
        'porcino' => ['key' => 'porcino', 'name' => 'Porcino', 'title' => 'Listado de cortes porcinos', 'displayWeights' => [80, 90, 100, 110, 120],        'defaultWeight' => 100],
        'aviar'   => ['key' => 'avicola', 'name' => 'Avicola', 'title' => 'Listado de cortes avicolas', 'displayWeights' => [2, 3, 4, 5],                   'defaultWeight' => 3],
    ];

    $categoryData = [];
    foreach ($config as $typeName => $cfg) {
        $type = AnimalType::where('nombre', $typeName)->first();
        if (! $type) {
            continue;
        }

        // Load all reference averages for this type; use the first peso_referencia as the base
        $averages = CutReferenceAverage::with('cutCatalog')
            ->where('animal_type_id', $type->id)
            ->whereHas('cutCatalog', fn ($q) => $q->where('activo', true)->whereNull('user_id'))
            ->orderBy('peso_referencia')
            ->get();

        if ($averages->isEmpty()) {
            continue;
        }

        // Pick the most common (or first) reference weight as base
        $baseWeight = (float) $averages->first()->peso_referencia;

        // Build baseCuts from all records at the base weight
        $baseCuts = $averages
            ->filter(fn ($r) => (float) $r->peso_referencia === $baseWeight)
            ->map(fn ($r) => [
                'name'  => $r->cutCatalog->nombre_canonico,
                'avgKg' => (float) $r->kg_promedio,
            ])
            ->values()
            ->all();

        // Scale cuts proportionally for each display weight
        $cutsByWeight = [];
        foreach ($cfg['displayWeights'] as $displayWeight) {
            $ratio = $displayWeight / $baseWeight;
            $cutsByWeight[$displayWeight] = array_map(fn ($cut) => [
                'name'  => $cut['name'],
                'avgKg' => round($cut['avgKg'] * $ratio, 3),
            ], $baseCuts);
        }

        $categoryData[$cfg['key']] = [
            'key'          => $cfg['key'],
            'name'         => $cfg['name'],
            'title'        => $cfg['title'],
            'weights'      => $cfg['displayWeights'],
            'defaultWeight' => $cfg['defaultWeight'],
            'cutsByWeight' => $cutsByWeight,
        ];
    }

    return view('welcome', [
        'categoryData' => $categoryData,
        'cutVisibility' => Cache::get('landing_cut_visibility', []),
    ]);
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

    return redirect()->route('landing');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'verified'])->group(function () {
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

    Route::get('/billing/planes', BillingPlans::class)->name('billing.plans');
    Route::get('/billing/pasarela/{plan}', BillingCheckout::class)->name('billing.checkout');

    Route::get('/admin/config', AdminConfigIndex::class)->name('admin.config.index');
    Route::get('/admin/config/usuarios', AdminUsuariosEstado::class)->name('admin.config.usuarios');
    Route::get('/admin/config/cortes-por-tipo', AdminCorteTipoAudit::class)->name('admin.config.cortes');

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
