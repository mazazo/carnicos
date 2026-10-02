<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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

        // Email de verificación de cuenta, en castellano.
        VerifyEmail::toMailUsing(fn (object $usuario, string $url) => (new MailMessage)
            ->subject('Confirmá tu email en Carnico')
            ->greeting('¡Hola '.($usuario->name ?? '').'!')
            ->line('Gracias por crear tu cuenta en Carnico. Para empezar a usar el sistema, confirmá tu email con el botón:')
            ->action('Confirmar mi email', $url)
            ->line('El enlace vence en 60 minutos. Si no creaste una cuenta en Carnico, ignorá este mensaje.')
            ->salutation('Saludos, el equipo de Carnico'));
    }
}
