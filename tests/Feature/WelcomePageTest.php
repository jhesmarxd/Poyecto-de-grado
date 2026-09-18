<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_portada_muestra_el_nuevo_hero_y_sus_accesos_publicos(): void
    {
        Cancha::factory()->create(['estado' => Cancha::ESTADO_DISPONIBLE]);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('class="welcome-header"', false)
            ->assertSee('Reserva tu cancha')
            ->assertSee('de forma fácil')
            ->assertSee('img/wally/welcome-cancha.png')
            ->assertSee('id="servicios"', false)
            ->assertSee('data-chatbot-open', false)
            ->assertSee(route('disponibilidad.index'), false)
            ->assertSee(route('canchas.index'), false)
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false);
    }

    public function test_la_portada_autenticada_muestra_los_accesos_de_la_cuenta(): void
    {
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);

        $this->actingAs($cliente)
            ->get(route('inicio'))
            ->assertOk()
            ->assertSee('Mi panel')
            ->assertSee('Cerrar sesión')
            ->assertSee(route('reservas.mis'), false)
            ->assertDontSee('Registrarse');
    }

    public function test_las_otras_paginas_publicas_conservan_la_cabecera_original(): void
    {
        $this->get(route('canchas.index'))
            ->assertOk()
            ->assertSee('class="public-topbar"', false)
            ->assertDontSee('class="welcome-header"', false);

        $this->get(route('disponibilidad.index'))
            ->assertOk()
            ->assertSee('class="public-topbar"', false)
            ->assertDontSee('class="welcome-header"', false);
    }
}
