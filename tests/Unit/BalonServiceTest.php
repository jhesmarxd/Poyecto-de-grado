<?php

namespace Tests\Unit;

use App\Models\Balon;
use App\Services\BalonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BalonServiceTest extends TestCase
{
    use RefreshDatabase;

    private BalonService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BalonService();
    }

    public function test_quitar_destacado_del_tipo_marca_como_no_destacado(): void
    {
        $balon1 = Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Test1',
            'nombre' => '1',
            'precio_por_hora' => 25.00,
            'destacado' => true,
        ]);

        $balon2 = Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Test2',
            'nombre' => '2',
            'precio_por_hora' => 25.00,
            'destacado' => true,
        ]);

        $this->service->quitarDestacadoDelTipo(Balon::TIPO_ECONOMICO, $balon1->id);

        $this->assertDatabaseHas('balones', [
            'id' => $balon1->id,
            'destacado' => true,
        ]);

        $this->assertDatabaseHas('balones', [
            'id' => $balon2->id,
            'destacado' => false,
        ]);
    }

    public function test_quitar_destacado_del_tipo_sin_excepción_marca_todos_como_no_destacado(): void
    {
        $balon1 = Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Test1',
            'nombre' => '1',
            'precio_por_hora' => 25.00,
            'destacado' => true,
        ]);

        $balon2 = Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Test2',
            'nombre' => '2',
            'precio_por_hora' => 25.00,
            'destacado' => true,
        ]);

        $this->service->quitarDestacadoDelTipo(Balon::TIPO_ECONOMICO);

        $this->assertDatabaseHas('balones', [
            'id' => $balon1->id,
            'destacado' => false,
        ]);

        $this->assertDatabaseHas('balones', [
            'id' => $balon2->id,
            'destacado' => false,
        ]);
    }

    public function test_listar_balones_ordena_por_tipo_marca_nombre(): void
    {
        Balon::create([
            'tipo' => Balon::TIPO_PREMIUM,
            'marca' => 'Mikasa',
            'nombre' => 'V200W',
            'precio_por_hora' => 30.00,
        ]);

        Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Asatex',
            'nombre' => '1',
            'precio_por_hora' => 25.00,
        ]);

        $balones = $this->service->listarBalones();

        $this->assertEquals(2, $balones->count());
        $this->assertEquals(Balon::TIPO_ECONOMICO, $balones->first()->tipo);
        $this->assertEquals(Balon::TIPO_PREMIUM, $balones->last()->tipo);
    }
}
