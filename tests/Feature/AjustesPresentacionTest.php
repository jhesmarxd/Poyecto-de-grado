<?php

namespace Tests\Feature;

use App\Models\Ajuste;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AjustesPresentacionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => User::ROL_ADMIN]);
    }

    public function test_un_admin_puede_actualizar_el_horario_informativo(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.ajustes.update'), [
                'color_primario' => '#008C95',
                'color_acento' => '#B8E000',
                'color_oscuro' => '#071A2B',
                'horario_atencion_dia_inicio' => 2,
                'horario_atencion_dia_fin' => 7,
                'horario_atencion_hora_inicio' => '09:00',
                'horario_atencion_hora_fin' => '21:30',
            ])
            ->assertRedirect(route('admin.ajustes.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('ajustes', [
            'clave' => 'horario_atencion_dia_inicio',
            'valor' => '2',
        ]);
        $this->assertDatabaseHas('ajustes', [
            'clave' => 'horario_atencion_hora_fin',
            'valor' => '21:30',
        ]);

        $this->assertSame([
            'dia_inicio' => 2,
            'dia_fin' => 7,
            'dia_inicio_nombre' => 'Martes',
            'dia_fin_nombre' => 'Domingo',
            'hora_inicio' => '09:00',
            'hora_fin' => '21:30',
        ], Ajuste::horarioAtencion());
    }

    public function test_el_horario_informativo_rechaza_un_rango_invalido(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.ajustes.index'))
            ->put(route('admin.ajustes.update'), [
                'color_primario' => '#008C95',
                'color_acento' => '#B8E000',
                'color_oscuro' => '#071A2B',
                'horario_atencion_dia_inicio' => 6,
                'horario_atencion_dia_fin' => 1,
                'horario_atencion_hora_inicio' => '22:00',
                'horario_atencion_hora_fin' => '08:00',
            ])
            ->assertRedirect(route('admin.ajustes.index'))
            ->assertSessionHasErrors([
                'horario_atencion_dia_fin',
                'horario_atencion_hora_fin',
            ]);
    }

    public function test_la_lista_de_usuarios_muestra_posiciones_y_no_ids_como_numero_visible(): void
    {
        $admin = $this->admin();
        User::factory()->create(['id' => 30]);
        User::factory()->create(['id' => 75]);

        $html = $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('data-sequential-rows', false)
            ->assertSee('<th>N.º</th>', false)
            ->getContent();

        preg_match('/<table id="tabla-usuarios".*?<tbody>(.*?)<\/tbody>/s', $html, $body);
        preg_match_all('/<tr>\s*<td>(\d+)<\/td>/s', $body[1] ?? '', $numeros);

        $this->assertSame(['1', '2', '3'], $numeros[1]);
    }

    public function test_la_numeracion_paginada_continua_en_la_pagina_siguiente(): void
    {
        $recepcionista = User::factory()->create(['rol' => User::ROL_RECEPCIONISTA]);

        foreach (range(1, 21) as $numero) {
            User::factory()->create([
                'name' => sprintf('Cliente %02d', $numero),
                'rol' => User::ROL_CLIENTE,
            ]);
        }

        $html = $this->actingAs($recepcionista)
            ->get(route('recepcionista.clientes.buscar', ['page' => 2]))
            ->assertOk()
            ->getContent();

        preg_match('/<tbody>\s*<tr>\s*<td>(\d+)<\/td>/s', $html, $primeraFila);

        $this->assertSame('21', $primeraFila[1] ?? null);
    }
}
