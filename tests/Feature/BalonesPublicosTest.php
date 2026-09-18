<?php

namespace Tests\Feature;

use App\Models\Balon;
use App\Models\Cancha;
use App\Models\User;
use Database\Seeders\BalonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalonesPublicosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_catalogo_inicial_utiliza_las_imagenes_png_disponibles(): void
    {
        $this->seed(BalonSeeder::class);

        $this->assertDatabaseCount('balones', 8);

        Balon::all()->each(function (Balon $balon): void {
            $this->assertStringEndsWith('.png', $balon->imagen);
            $this->assertFileExists(public_path('img/balones/'.$balon->imagen));
        });
    }

    public function test_el_endpoint_de_version_devuelve_el_catalogo_de_balones(): void
    {
        Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Asatex',
            'nombre' => '1',
            'precio_por_hora' => 25.00,
        ]);
        Balon::create([
            'tipo' => Balon::TIPO_PREMIUM,
            'marca' => 'Mikasa',
            'nombre' => 'V200W',
            'precio_por_hora' => 30.00,
        ]);

        $response = $this->getJson('/balones/version');

        $response->assertOk()
            ->assertJsonStructure(['version', 'balones'])
            ->assertJsonFragment(['precio_por_hora' => 30.00])
            ->assertJsonFragment(['marca' => 'Mikasa']);
    }

    public function test_el_detalle_publico_muestra_especificaciones_y_tipo_cerrada(): void
    {
        $cancha = Cancha::factory()->create([
            'nombre' => 'Cancha 1',
            'color_malla' => 'Verde',
            'altura_malla' => '2 m',
            'tipo_piso' => 'Cemento pulido',
            'tipo_recinto' => 'cerrada',
        ]);

        $this->get('/canchas/'.$cancha->id)
            ->assertOk()
            ->assertSee('Color de la malla')
            ->assertSee('Verde')
            ->assertSee('Altura de la malla')
            ->assertSee('Tipo de piso')
            ->assertSee('Cemento pulido')
            ->assertSee('Cerrada (techada, no campo abierto)');
    }

    public function test_la_pagina_publica_muestra_el_mini_catalogo_de_balones(): void
    {
        Cancha::factory()->create(['nombre' => 'Cancha 1']);

        Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Veloz',
            'nombre' => '1',
            'color' => 'Blanco/azul',
            'calidad' => 'Estándar',
            'precio_por_hora' => 25.00,
        ]);

        $this->get('/canchas')
            ->assertOk()
            ->assertSee('Balones económicos que presta la dueña')
            ->assertSee('Veloz')
            ->assertSee('Blanco/azul');
    }

    public function test_un_admin_puede_crear_un_balon_del_catalogo(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);

        $this->actingAs($admin)
            ->post('/admin/balones', [
                'tipo' => Balon::TIPO_PREMIUM,
                'marca' => 'Molten',
                'nombre' => '5500',
                'color' => 'Blanco/rojo',
                'calidad' => 'Profesional',
                'precio_por_hora' => 30.00,
            ])
            ->assertRedirect(route('admin.balones.index'));

        $this->assertDatabaseHas('balones', [
            'tipo' => Balon::TIPO_PREMIUM,
            'marca' => 'Molten',
            'precio_por_hora' => 30.00,
        ]);
    }

    public function test_un_cliente_no_puede_crear_balones_en_el_panel_admin(): void
    {
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);

        $this->actingAs($cliente)
            ->post('/admin/balones', [
                'tipo' => Balon::TIPO_ECONOMICO,
                'nombre' => 'X',
                'precio_por_hora' => 25.00,
            ])
            ->assertForbidden();
    }
}
