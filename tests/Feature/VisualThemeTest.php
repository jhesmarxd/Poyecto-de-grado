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

    public function test_google_es_la_ultima_opcion_del_login_y_no_aparece_en_registro(): void
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
            ->assertDontSee('Continuar con Google')
            ->assertDontSee('Registrarse con Google')
            ->assertDontSee(route('auth.google.redirect'), false);
    }

    public function test_recuperar_password_conserva_el_panel_ilustrado(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('class="auth-shell "', false)
            ->assertSee('class="auth-aside"', false)
            ->assertSee('Tu próxima jugada empieza aquí.');
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

    public function test_la_paleta_deportiva_es_el_nuevo_valor_inicial(): void
    {
        $this->assertDatabaseHas('ajustes', ['clave' => 'color_primario', 'valor' => '#008C95']);
        $this->assertDatabaseHas('ajustes', ['clave' => 'color_acento', 'valor' => '#B8E000']);
        $this->assertDatabaseHas('ajustes', ['clave' => 'color_oscuro', 'valor' => '#071A2B']);
    }

    public function test_el_horario_informativo_tiene_valores_iniciales_en_la_base_de_datos(): void
    {
        $this->assertDatabaseHas('ajustes', ['clave' => 'horario_atencion_dia_inicio', 'valor' => '1']);
        $this->assertDatabaseHas('ajustes', ['clave' => 'horario_atencion_dia_fin', 'valor' => '6']);
        $this->assertDatabaseHas('ajustes', ['clave' => 'horario_atencion_hora_inicio', 'valor' => '08:00']);
        $this->assertDatabaseHas('ajustes', ['clave' => 'horario_atencion_hora_fin', 'valor' => '22:00']);
    }
}
