<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('suscripciones:vencer', function (App\Services\Suscripciones $suscripciones) {
    $this->info('Suscripciones vencidas: '.$suscripciones->vencer());
})->purpose('Marca vencidas las pruebas y planes cuyo período terminó');

Illuminate\Support\Facades\Schedule::command('suscripciones:vencer')->hourly();
