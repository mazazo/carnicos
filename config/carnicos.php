<?php

return [
    /*
    | Días de prueba gratis al registrarse (con acceso de Plan 1). El
    | administrador puede sumar días a cada carnicería desde su panel.
    */
    'dias_prueba' => (int) env('CARNICOS_DIAS_PRUEBA', 5),

    /* Plan con el que se da la prueba gratis. */
    'plan_prueba' => env('CARNICOS_PLAN_PRUEBA', 'plan-1'),

    'moneda' => 'ARS',

    /*
    | Pagos. Hasta cargar credenciales, solo se cobra de forma manual
    | (transferencia/efectivo registrados por el administrador).
    | Para probar Mercado Pago usar credenciales de PRUEBA (sandbox).
    */
    'pagos' => [
        'transferencia' => [
            'habilitada' => (bool) env('CARNICOS_TRANSFERENCIA', true),
            'instrucciones' => env('CARNICOS_TRANSFERENCIA_DATOS', 'Pedí los datos bancarios a administración y avisá el pago desde esta pantalla.'),
        ],
        'mercadopago' => [
            'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
            'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
            'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
            'base_url' => env('MERCADOPAGO_BASE_URL', 'https://api.mercadopago.com'),
            'sandbox' => (bool) env('MERCADOPAGO_SANDBOX', true),
        ],
    ],
];
