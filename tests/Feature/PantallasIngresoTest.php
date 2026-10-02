<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaCarnicerias;
use Tests\TestCase;

/** Ingreso y registro: solo la tarjeta de bienvenida, sin la barra de la app. */
class PantallasIngresoTest extends TestCase
{
    use CreaCarnicerias;
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

    /**
     * El diseño de invitados es el layout: la vista del componente es una sola
     * tarjeta. Si la vista trae su propio <html>, Livewire no encuentra el
     * componente al enviar el formulario y la página queda en blanco.
     */
    public function test_cada_pantalla_es_un_solo_documento_con_el_componente_adentro(): void
    {
        foreach (['register', 'login'] as $ruta) {
            $html = $this->get(route($ruta))->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, '<html'), "$ruta: más de un <html>");
            $this->assertSame(1, substr_count($html, '<body'), "$ruta: más de un <body>");
            $this->assertMatchesRegularExpression('#<section[^>]*>\s*<div wire:snapshot=#', $html, "$ruta: el componente no está dentro de la tarjeta");
        }
    }

    public function test_al_registrarse_va_a_planes(): void
    {
        $this->tiposDeAnimal();
        $this->plan('plan-1');

        \Livewire\Livewire::test(\App\Livewire\Auth\Register::class)
            ->set('carniceria', 'Carnes del Sur')->set('name', 'Ana')->set('email', 'ana@carnes.com')
            ->set('movil', '1155557777')->set('password', 'Clave123!x')->set('password_confirmation', 'Clave123!x')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('billing.plans'));

        $this->get(route('billing.plans'))->assertOk()->assertSee('Carnes del Sur');
    }
}
