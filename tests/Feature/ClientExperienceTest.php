<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_panel_del_cliente_presenta_la_experiencia_accesible_sin_consultas_adicionales(): void
    {
        $cliente = User::factory()->create([
            'rol' => User::ROL_CLIENTE,
            'name' => 'Juan Pablo García',
        ]);

        $this->actingAs($cliente)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('class="client-ui"', false)
            ->assertSee('class="client-home-hero"', false)
            ->assertDontSee('client-home-hero__visual', false)
            ->assertSee('¡Hola, <span>Juan</span>!', false)
            ->assertSeeInOrder(['Reservar cancha', 'Mis reservas', 'Abrir asistente'])
            ->assertSee('Solo necesitas tres pasos');
    }

    public function test_disponibilidad_conserva_el_formulario_publico_y_adopta_los_tres_pasos(): void
    {
        Cancha::factory()->create();
        $this->get(route('disponibilidad.index'))
            ->assertOk()
            ->assertSee('class="availability-wizard card"', false)
            ->assertSee('name="cancha_id"', false)
            ->assertSee('name="fecha"', false)
            ->assertSeeInOrder(['Elige la cancha', 'Elige la fecha', 'Consulta los horarios'])
            ->assertDontSee('class="client-ui"', false);

        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);

        $this->actingAs($cliente)
            ->get(route('disponibilidad.index'))
            ->assertOk()
            ->assertSee('class="client-ui"', false)
            ->assertSee('class="client-bottom-nav"', false);
    }

    public function test_el_visitante_retoma_su_cancha_y_fecha_despues_de_registrarse(): void
    {
        $cancha = Cancha::factory()->create([
            'nombre' => 'Cancha Central',
            'descripcion' => 'Piso sintético y graderías.',
            'imagen' => 'cancha-1.jpeg',
        ]);
        $fecha = now()->addDay()->toDateString();
        $destino = route('disponibilidad.index', ['cancha_id' => $cancha->id, 'fecha' => $fecha]);

        $this->get(route('disponibilidad.index'))
            ->assertOk()
            ->assertSee('cancha-picker__card')
            ->assertSee('Piso sintético y graderías.')
            ->assertSee('cancha-1.jpeg');

        $this->get(route('disponibilidad.index', [
            'consultar' => 1, 'cancha_id' => $cancha->id, 'fecha' => $fecha,
        ]))->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Inicia sesión o regístrate para consultar los horarios de');

        $this->post(route('register'), [
            'name' => 'Cliente Nuevo',
            'email' => 'nuevo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect($destino);

        $this->get($destino)
            ->assertOk()
            ->assertSee('data-cancha-name="Cancha Central"', false)
            ->assertSee('checked', false)
            ->assertSee('value="'.$fecha.'"', false);
    }

    public function test_el_visitante_retoma_su_consulta_despues_de_iniciar_sesion(): void
    {
        $cancha = Cancha::factory()->create();
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE, 'password' => 'password123']);
        $fecha = now()->addDay()->toDateString();
        $destino = route('disponibilidad.index', ['cancha_id' => $cancha->id, 'fecha' => $fecha]);

        $this->get(route('disponibilidad.index', [
            'consultar' => 1, 'cancha_id' => $cancha->id, 'fecha' => $fecha,
        ]))->assertRedirect(route('login'));

        $this->post(route('login'), [
            'email' => $cliente->email,
            'password' => 'password123',
        ])->assertRedirect($destino);
    }

    public function test_perfil_y_notificaciones_utilizan_los_componentes_del_cliente(): void
    {
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);

        $this->actingAs($cliente)
            ->get(route('perfil.edit'))
            ->assertOk()
            ->assertSee('class="client-profile-grid"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="telefono"', false)
            ->assertSee('name="password"', false);

        $this->actingAs($cliente)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee('client-notifications-page', false)
            ->assertSee('Todavía no tienes notificaciones');
    }

    public function test_los_roles_operativos_no_reciben_el_shell_del_cliente(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('class="client-ui"', false)
            ->assertDontSee('class="client-bottom-nav"', false);
    }
}
