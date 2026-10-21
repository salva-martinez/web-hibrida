<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistroFisioTest extends TestCase
{
    use RefreshDatabase;

    public function test_cualquiera_puede_registrarse_como_fisioterapeuta_sin_invitacion(): void
    {
        Notification::fake();

        $response = $this->post(route('register'), [
            'nombre' => 'Nuevo',
            'apellido1' => 'Fisio',
            'apellido2' => 'Registrado',
            'email' => 'nuevo@clinica.com',
            'password' => 'secreto1234',
            'password_confirmation' => 'secreto1234',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@clinica.com',
            'role' => 'fisio',
            'fisio_id' => null,
        ]);
    }

    public function test_la_cuenta_nace_sin_verificar_y_no_puede_entrar_al_panel(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'nombre' => 'Nuevo',
            'apellido1' => 'Fisio',
            'apellido2' => 'Registrado',
            'email' => 'nuevo@clinica.com',
            'password' => 'secreto1234',
            'password_confirmation' => 'secreto1234',
        ]);

        $user = User::where('email', 'nuevo@clinica.com')->first();

        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->hasVerifiedEmail());

        // La sesión queda iniciada, pero el panel exige verificación.
        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_el_login_se_rechaza_hasta_verificar_el_email(): void
    {
        $fisio = User::factory()->fisio()->unverified()->create([
            'email' => 'sinverificar@clinica.com',
            'password' => 'secreto1234',
        ]);

        $this->post(route('login'), [
            'login_type' => 'fisio',
            'email' => 'sinverificar@clinica.com',
            'password' => 'secreto1234',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_el_login_funciona_tras_verificar_el_email(): void
    {
        $fisio = User::factory()->fisio()->create([
            'email' => 'verificado@clinica.com',
            'password' => 'secreto1234',
        ]);

        $this->post(route('login'), [
            'login_type' => 'fisio',
            'email' => 'verificado@clinica.com',
            'password' => 'secreto1234',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($fisio);
    }

    public function test_el_enlace_de_verificacion_activa_la_cuenta(): void
    {
        Event::fake();

        $fisio = User::factory()->fisio()->unverified()->create();

        // El enlace real es una URL firmada con caducidad.
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $fisio->id,
            'hash' => sha1($fisio->getEmailForVerification()),
        ]);

        $this->actingAs($fisio)->get($url)->assertRedirect(route('admin.dashboard'));

        $this->assertTrue($fisio->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_un_enlace_de_verificacion_invalido_no_activa_la_cuenta(): void
    {
        Event::fake();

        $fisio = User::factory()->fisio()->unverified()->create();

        // Sin firma válida: el middleware "signed" bloquea el enlace.
        $this->actingAs($fisio)->get(route('verification.verify', [
            'id' => $fisio->id,
            'hash' => sha1($fisio->getEmailForVerification()),
        ]));

        $this->assertFalse($fisio->fresh()->hasVerifiedEmail());
    }

    public function test_se_puede_reenviar_el_email_de_verificacion(): void
    {
        Notification::fake();

        $fisio = User::factory()->fisio()->unverified()->create();

        $this->actingAs($fisio)
            ->post(route('verification.send'))
            ->assertSessionHas('success');

        Notification::assertSentTo($fisio, VerifyEmail::class);
    }

    public function test_el_registro_no_permite_crear_pacientes(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'nombre' => 'Imposible',
            'apellido1' => 'Ser',
            'apellido2' => 'Paciente',
            'email' => 'paciente@clinica.com',
            'password' => 'secreto1234',
            'password_confirmation' => 'secreto1234',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'paciente@clinica.com',
            'role' => 'fisio',
        ]);
    }

    public function test_no_se_pueden_registrar_dos_cuentas_con_el_mismo_email(): void
    {
        Notification::fake();

        User::factory()->fisio()->create(['email' => 'duplicado@clinica.com']);

        $this->post(route('register'), [
            'nombre' => 'Otro',
            'apellido1' => 'Fisio',
            'apellido2' => 'Repetido',
            'email' => 'duplicado@clinica.com',
            'password' => 'secreto1234',
            'password_confirmation' => 'secreto1234',
        ])->assertSessionHasErrors('email');
    }
}
