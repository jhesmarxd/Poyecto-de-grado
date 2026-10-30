<?php

namespace Tests\Feature;

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
