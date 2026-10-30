<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CanchaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_admin_sube_una_imagen_real_para_la_cancha(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $imagen = UploadedFile::fake()->image('cancha-principal.png', 640, 360);

        $this->actingAs($admin)
            ->post(route('admin.canchas.store'), [
                'nombre' => 'Cancha Principal',
                'descripcion' => 'Cancha techada',
                'precio_por_hora' => 50,
                'estado' => Cancha::ESTADO_DISPONIBLE,
                'tipo_recinto' => 'cerrada',
                'imagen' => $imagen,
            ])
            ->assertRedirect(route('admin.canchas.index'));

        $cancha = Cancha::query()->sole();
        $ruta = public_path('img/canchas/'.$cancha->imagen);

        try {
            $this->assertStringEndsWith('.png', $cancha->imagen);
            $this->assertFileExists($ruta);
            $this->assertNotNull($cancha->imagenUrl());
        } finally {
            if (is_file($ruta)) {
                unlink($ruta);
            }
        }
    }
}
