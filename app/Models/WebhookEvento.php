<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Aviso recibido de una pasarela de pago (se guarda y se procesa una sola vez). */
class WebhookEvento extends Model
{
    protected $table = 'webhook_eventos';

    protected $fillable = ['proveedor', 'evento_id', 'tipo', 'recurso_id', 'payload', 'estado', 'error', 'procesado_at'];

    protected $casts = [
        'payload' => 'array',
        'procesado_at' => 'datetime',
    ];
}
