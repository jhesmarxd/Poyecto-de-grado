<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorarioManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => User::ROL_ADMIN]);
    }

    public function test_un_admin_puede_crear_un_horario(): void
    {
        $cancha = Cancha::factory()->create();

        $this->actingAs($this->admin())
            ->post("/admin/canchas/{$cancha->id}/horarios", [
                'dia_semana' => 2,
                'hora_inicio' => '10:00',
                'hora_fin' => '11:00',
                'disponible' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('horarios', [
            'cancha_id' => $cancha->id,
            'dia_semana' => 2,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'disponible' => true,
        ]);
    }

    public function test_no_se_puede_crear_un_horario_superpuesto(): void
    {
        $cancha = Cancha::factory()->create();
        $cancha->horarios()->create([
            'dia_semana' => 1,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '20:00:00',
        ]);

        $response = $this->actingAs($this->admin())
            ->post("/admin/canchas/{$cancha->id}/horarios", [
                'dia_semana' => 1,
                'hora_inicio' => '19:00',
                'hora_fin' => '21:00',
            ]);

        $response->assertSessionHasErrors('hora_fin');
        $this->assertDatabaseCount('horarios', 1);
    }

    public function test_se_permiten_horarios_contiguos(): void
    {
        $cancha = Cancha::factory()->create();
        $cancha->horarios()->create([
            'dia_semana' => 1,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);

        $this->actingAs($this->admin())
            ->post("/admin/canchas/{$cancha->id}/horarios", [
                'dia_semana' => 1,
                'hora_inicio' => '19:00',
                'hora_fin' => '20:00',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('horarios', 2);
    }

    public function test_la_hora_fin_debe_ser_posterior_a_la_hora_inicio(): void
    {
        $cancha = Cancha::factory()->create();

        $this->actingAs($this->admin())
            ->post("/admin/canchas/{$cancha->id}/horarios", [
                'dia_semana' => 1,
                'hora_inicio' => '20:00',
                'hora_fin' => '19:00',
            ])
            ->assertSessionHasErrors('hora_fin');

        $this->assertDatabaseCount('horarios', 0);
    }

    public function test_un_cliente_no_puede_gestionar_horarios(): void
    {
        $cancha = Cancha::factory()->create();

        $this->actingAs(User::factory()->create(['rol' => User::ROL_CLIENTE]))
            ->get("/admin/canchas/{$cancha->id}/horarios/crear")
            ->assertForbidden();
    }

    public function test_editar_un_horario_sin_cambiar_su_rango_no_genera_error_de_superposicion(): void
    {
        $cancha = Cancha::factory()->create();
        $horario = $cancha->horarios()->create([
            'dia_semana' => 1,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/canchas/{$cancha->id}/horarios/{$horario->id}", [
                'dia_semana' => 1,
                'hora_inicio' => '18:00',
                'hora_fin' => '19:00',
                'disponible' => '0',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('horarios', [
            'id' => $horario->id,
            'disponible' => false,
        ]);
    }

    public function test_no_se_puede_editar_un_horario_hacia_una_franja_superpuesta(): void
    {
        $cancha = Cancha::factory()->create();
        $a = $cancha->horarios()->create([
            'dia_semana' => 1,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);
        $b = $cancha->horarios()->create([
            'dia_semana' => 1,
            'hora_inicio' => '19:00:00',
            'hora_fin' => '20:00:00',
        ]);

        // Mover B hacia una franja que solapa a A
        $this->actingAs($this->admin())
            ->put("/admin/canchas/{$cancha->id}/horarios/{$b->id}", [
                'dia_semana' => 1,
                'hora_inicio' => '18:30',
                'hora_fin' => '19:30',
            ])
            ->assertSessionHasErrors('hora_fin');

        $this->assertDatabaseHas('horarios', [
            'id' => $b->id,
            'hora_inicio' => '19:00:00',
        ]);
    }

    public function test_un_admin_configura_una_semana_completa_por_periodo(): void
    {
        $cancha = Cancha::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.horarios.bulk', $cancha), [
                'dias' => [1, 2, 3, 4, 5, 6],
                'vigente_desde' => '2026-10-01',
                'vigente_hasta' => '2026-10-31',
                'hora_inicio' => '09:00',
                'hora_fin' => '22:00',
                'duracion' => 60,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('horarios', 78);
        $horario = $cancha->horarios()
            ->where('dia_semana', 6)
            ->where('hora_inicio', '21:00:00')
            ->sole();
        $this->assertSame('2026-10-01', $horario->vigente_desde->toDateString());
        $this->assertSame('2026-10-31', $horario->vigente_hasta->toDateString());
        $this->assertSame('22:00:00', $horario->hora_fin);
    }

    public function test_un_cierre_excepcional_oculta_todas_las_franjas_de_la_fecha(): void
    {
        $cancha = Cancha::factory()->create();
        $fecha = now()->addDays(3);
        $cancha->horarios()->create([
            'dia_semana' => $fecha->dayOfWeekIso,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
            'disponible' => true,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.horarios.closures.store', $cancha), [
                'fecha_cierre' => $fecha->toDateString(),
                'motivo' => 'Mantenimiento',
            ])
            ->assertRedirect();

        $this->assertTrue($cancha->horariosDisponiblesPara($fecha->toDateString())->isEmpty());
    }
}
