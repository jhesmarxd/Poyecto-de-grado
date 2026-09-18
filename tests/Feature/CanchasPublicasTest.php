<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\Horario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanchasPublicasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_catalogo_publico_muestra_solo_canchas_disponibles(): void
    {
        Cancha::factory()->create([
            'nombre' => 'Cancha Visible',
            'estado' => Cancha::ESTADO_DISPONIBLE,
        ]);
        Cancha::factory()->create([
            'nombre' => 'Cancha Oculta',
            'estado' => Cancha::ESTADO_MANTENIMIENTO,
        ]);

        $response = $this->get('/canchas')->assertOk();

        $response->assertSee('Cancha Visible');
        $response->assertDontSee('Cancha Oculta');
    }

    public function test_el_detalle_publico_muestra_un_solo_horario_general_informativo(): void
    {
        $cancha = Cancha::factory()->create();
        $cancha->horarios()->create([
            'dia_semana' => Horario::LUNES,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
            'disponible' => true,
        ]);

        $this->get("/canchas/{$cancha->id}")
            ->assertOk()
            ->assertSee($cancha->nombre)
            ->assertSee('Horarios de atención')
            ->assertSee('Lunes')
            ->assertSee('a sábado')
            ->assertSee('08:00')
            ->assertSee('22:00')
            ->assertDontSee('18:00');
    }

    public function test_el_detalle_de_una_cancha_inexistente_devuelve_404(): void
    {
        $this->get('/canchas/9999')->assertNotFound();
    }
}
