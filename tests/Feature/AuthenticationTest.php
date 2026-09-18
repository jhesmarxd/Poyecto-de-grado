<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_puede_registrarse_y_recibe_rol_cliente(): void
    {
        $response = $this->post('/register', [
            'name' => 'Usuario Nuevo',
            'email' => 'nuevo@wally.com',
            'telefono' => '71234569',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/home');

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@wally.com',
            'rol' => User::ROL_CLIENTE,
        ]);

        $this->assertAuthenticated();
    }

    public function test_un_usuario_puede_iniciar_sesion(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    public function test_un_usuario_no_puede_iniciar_sesion_con_credenciales_incorrectas(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('Password123!'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'ClaveIncorrecta',
        ]);

        $this->assertGuest();
    }

    public function test_un_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('Password123!'),
            'estado' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
    }

    public function test_un_admin_es_redirigido_a_su_panel(): void
    {
        $admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'password' => bcrypt('Password123!'),
        ]);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_un_recepcionista_es_redirigido_a_la_agenda_del_dia(): void
    {
        $recepcionista = User::factory()->create([
            'rol' => User::ROL_RECEPCIONISTA,
            'password' => bcrypt('Password123!'),
        ]);

        $this->post('/login', [
            'email' => $recepcionista->email,
            'password' => 'Password123!',
        ])->assertRedirect(route('recepcionista.reservas.hoy'));
    }
}
