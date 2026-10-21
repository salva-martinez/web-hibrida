<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * El enlace de verificación llega por email y se pulsa desde cualquier
 * dispositivo. Si exigiera sesión iniciada, desde el móvil (donde no la hay)
 * el enlace redirigía al login en bucle y el usuario no podía activar su
 * cuenta.
 */
class VerificarEmailEnMovilTest extends TestCase
{
    use RefreshDatabase;

    private function enlace(User $usuario): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $usuario->getKey(),
            'hash' => sha1($usuario->getEmailForVerification()),
        ]);
    }

    public function test_el_enlace_verifica_la_cuenta_sin_sesion_iniciada(): void
    {
        Event::fake();

        $fisio = User::factory()->fisio()->unverified()->create();

        // Sin actingAs: es el caso de abrir el enlace en el móvil.
        $this->get($this->enlace($fisio))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue($fisio->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);

        // Y además inicia sesión, para que entre directo al panel.
        $this->assertAuthenticatedAs($fisio->fresh());
    }

    public function test_el_enlace_es_valido_tambien_para_un_email_externo(): void
    {
        $fisio = User::factory()->fisio()->unverified()->create([
            'email' => 'mrfishandchipsok@gmail.com',
        ]);

        $this->get($this->enlace($fisio))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue($fisio->fresh()->hasVerifiedEmail());
    }

    public function test_un_enlace_sin_firma_no_verifica_nada(): void
    {
        $fisio = User::factory()->fisio()->unverified()->create();

        // Sin la firma: el middleware "signed" lo rechaza y el usuario vuelve
        // al login con un mensaje claro (no a un 403 ni a un bucle).
        $this->get("/email/verify/{$fisio->id}/".sha1($fisio->getEmailForVerification()))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertFalse($fisio->fresh()->hasVerifiedEmail());
    }

    public function test_un_hash_que_no_corresponde_no_verifica_nada(): void
    {
        $fisio = User::factory()->fisio()->unverified()->create();
        $otro = User::factory()->fisio()->unverified()->create();

        // Firma válida, pero el hash es de otra cuenta.
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $otro->getKey(),
            'hash' => sha1($fisio->getEmailForVerification()),
        ]);

        $this->get($url)->assertRedirect(route('login'));

        $this->assertFalse($fisio->fresh()->hasVerifiedEmail());
        $this->assertFalse($otro->fresh()->hasVerifiedEmail());
    }

    public function test_verificar_dos_veces_no_falla(): void
    {
        $fisio = User::factory()->fisio()->unverified()->create();

        $this->get($this->enlace($fisio))->assertRedirect(route('admin.dashboard'));
        $this->get($this->enlace($fisio))->assertRedirect(route('admin.dashboard'));

        $this->assertTrue($fisio->fresh()->hasVerifiedEmail());
    }

    public function test_el_enlace_no_cambia_la_sesion_de_otro_usuario(): void
    {
        $fisio = User::factory()->fisio()->unverified()->create();
        $otro = User::factory()->fisio()->create();

        $this->actingAs($otro)->get($this->enlace($fisio))->assertRedirect(route('admin.dashboard'));

        // Se verifica la cuenta del enlace, pero no se pisa la sesión actual.
        $this->assertTrue($fisio->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($otro);
    }
}
