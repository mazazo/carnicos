<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** El email de verificación de cuenta sale en castellano y a nombre de Carnico. */
class EmailVerificacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_email_de_verificacion_esta_en_castellano(): void
    {
        app()->setLocale('es');
        $user = User::factory()->unverified()->create(['name' => 'Ana']);

        $mail = (new VerifyEmail)->toMail($user);
        $html = (string) $mail->render();

        $this->assertSame('Confirmá tu email en Carnico', $mail->subject);
        $this->assertStringContainsString('¡Hola Ana!', $html);
        $this->assertStringContainsString('Confirmar mi email', $html);
        $this->assertStringContainsString('Saludos, el equipo de Carnico', $html);
        $this->assertStringContainsString('copiá y pegá este enlace', $html);
        $this->assertStringNotContainsString('Verify Email Address', $html);
    }

    public function test_se_envia_al_registrarse_desde_la_cuenta(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
