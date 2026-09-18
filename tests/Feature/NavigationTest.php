<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_cliente_ve_una_navegacion_reducida_y_conserva_el_layout_al_reservar(): void
    {
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);

        $this->actingAs($cliente)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Mi cuenta')
            ->assertSee('Mi panel')
            ->assertSee('Consultar y reservar')
            ->assertSee('Mis reservas')
            ->assertDontSee('Administración')
            ->assertDontSee('Recepción');

        $this->actingAs($cliente)
            ->get(route('disponibilidad.index'))
            ->assertOk()
            ->assertSee('d-sidebar')
            ->assertSee('Mi cuenta');
    }

    public function test_el_recepcionista_ve_solo_sus_accesos_principales_y_operaciones_contextuales(): void
    {
        $recepcionista = User::factory()->create(['rol' => User::ROL_RECEPCIONISTA]);

        $this->actingAs($recepcionista)
            ->get(route('recepcionista.reservas.hoy'))
            ->assertOk()
            ->assertSee('Recepción')
            ->assertSee('Agenda operativa')
            ->assertSee('Calendario operativo')
            ->assertSee('Registrar reserva')
            ->assertSee('Clientes y atención')
            ->assertSee('Historial de reservas')
            ->assertDontSee('Administración');
    }

    public function test_el_admin_ve_modulos_agrupados_y_los_submodulos_de_recursos(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Administración')
            ->assertSee('Panel operativo')
            ->assertSee('Reservas y agenda')
            ->assertSee('Recursos deportivos')
            ->assertSee('Usuarios y accesos')
            ->assertSee('Inteligencia y reportes')
            ->assertSee('Configuración e integraciones')
            ->assertSee('Calendario de reservas')
            ->assertSee('Gestión de reservas')
            ->assertSee('Reportes operativos')
            ->assertSee('Métricas del chatbot')
            ->assertSee('Conversaciones del chatbot')
            ->assertSee('Preferencias del sistema')
            ->assertSee('Google Calendar')
            ->assertSee('aria-expanded=', false)
            ->assertDontSee('Colores del sitio');

        $this->actingAs($admin)
            ->get(route('admin.canchas.index'))
            ->assertOk()
            ->assertSee('Canchas')
            ->assertSee('Franjas reservables')
            ->assertSee('Balones');
    }

    public function test_mis_reservas_lleva_a_cada_rol_operativo_a_su_panel(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $recepcionista = User::factory()->create(['rol' => User::ROL_RECEPCIONISTA]);

        $this->actingAs($admin)
            ->get(route('reservas.mis'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($recepcionista)
            ->get(route('reservas.mis'))
            ->assertRedirect(route('recepcionista.reservas.hoy'));
    }
}
