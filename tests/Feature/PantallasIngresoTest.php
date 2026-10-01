<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ingreso y registro: solo la tarjeta de bienvenida, sin la barra de la app. */
class PantallasIngresoTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_e_ingreso_muestran_solo_la_tarjeta(): void
    {
        foreach (['register', 'login'] as $ruta) {
            $this->get(route($ruta))
                ->assertOk()
                ->assertSee('Tu carnicería, ordenada')
                ->assertDontSee('<header', false)
                ->assertDontSee('Planes');
        }
    }
}
