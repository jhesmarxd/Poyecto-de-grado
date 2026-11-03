<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_login_presenta_la_nueva_experiencia_deportiva(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('auth-shell auth-shell--centered', false)
            ->assertDontSee('class="auth-aside"', false)
            ->assertSee('Wally Canchas');

        $this->assertFileExists(public_path('img/generated/login-wally-chibi-v1.png'));
        $this->assertFileExists(public_path('img/generated/wally-team-chibi-v1.png'));
    }

    public function test_google_aparece_en_login_y_registro_solo_cuando_esta_configurado(): void
    {
        config()->set('services.google', [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'redirect' => 'http://localhost/auth/google/callback',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSeeInOrder([
                'Iniciar sesión',
                'Continuar con Google',
                'Regístrate aquí',
            ]);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('auth-shell auth-shell--centered', false)
            ->assertDontSee('class="auth-aside"', false)
            ->assertSee('Continuar con Google')
            ->assertSee(route('auth.google.redirect'), false);

        config()->set('services.google.client_id', null);
        config()->set('services.google.client_secret', null);

        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee('Continuar con Google');
    }

    public function test_la_agenda_y_el_calendario_integran_las_guias_visuales(): void
    {
        $recepcionista = User::factory()->create(['rol' => User::ROL_RECEPCIONISTA]);
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);

        $this->actingAs($recepcionista)
            ->get(route('recepcionista.reservas.hoy'))
            ->assertOk()
            ->assertSee('Agenda en juego')
            ->assertSee('img/generated/wally-team-chibi-v1.png', false);

        $this->actingAs($admin)
            ->get(route('admin.reservas.calendario'))
            ->assertOk()
            ->assertSee('Calendario de reservas')
            ->assertSee('data-reservation-calendar', false)
            ->assertSee('calendar-legend', false);
    }

    public function test_la_paleta_deportiva_esta_definida_en_el_proyecto(): void
    {
        $this->assertSame('#008C95', config('theme.colors.color_primario'));
        $this->assertSame('#B8E000', config('theme.colors.color_acento'));
        $this->assertSame('#071A2B', config('theme.colors.color_oscuro'));
    }

    public function test_el_horario_informativo_tiene_valores_iniciales_en_la_base_de_datos(): void
    {
        $this->assertDatabaseHas('ajustes', ['clave' => 'horario_atencion_dia_inicio', 'valor' => '1']);
        $this->assertDatabaseHas('ajustes', ['clave' => 'horario_atencion_dia_fin', 'valor' => '6']);
        $this->assertDatabaseHas('ajustes', ['clave' => 'horario_atencion_hora_inicio', 'valor' => '08:00']);
        $this->assertDatabaseHas('ajustes', ['clave' => 'horario_atencion_hora_fin', 'valor' => '22:00']);
    }
}
