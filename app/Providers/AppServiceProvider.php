<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // En producción (https://carniox.com) los links que se mandan afuera
        // (aviso y vuelta de Mercado Pago, verificación de email) siempre en https,
        // aunque el servidor reciba http detrás de un proxy.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
