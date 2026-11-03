<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => User::ROL_ADMIN]);
    }

    private function cliente(): User
    {
        return User::factory()->create(['rol' => User::ROL_CLIENTE]);
    }

    public function test_un_admin_puede_ver_la_lista_de_usuarios(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/usuarios');

        $response->assertOk();
        $response->assertSee('Usuarios y accesos');
    }

    public function test_un_cliente_no_puede_acceder_a_la_gestion_de_usuarios(): void
    {
        $response = $this->actingAs($this->cliente())->get('/admin/usuarios');

        $response->assertForbidden();
    }

    public function test_un_visitante_no_puede_acceder_a_la_gestion_de_usuarios(): void
    {
        $this->get('/admin/usuarios')->assertRedirect('/login');
    }

    public function test_un_admin_puede_crear_un_cliente(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/usuarios', [
            'name' => 'Nuevo Empleado',
            'email' => 'empleado@wally.com',
            'telefono' => '71234570',
            'rol' => User::ROL_CLIENTE,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/admin/usuarios');
        $this->assertDatabaseHas('users', [
            'email' => 'empleado@wally.com',
            'rol' => User::ROL_CLIENTE,
        ]);
    }

    public function test_el_formulario_y_la_validacion_no_permiten_crear_otro_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/usuarios/crear')
            ->assertOk()
            ->assertDontSee('<option value="admin"', false);

        $this->post('/admin/usuarios', [
            'name' => 'Otro Administrador',
            'email' => 'otro-admin@wally.com',
            'rol' => User::ROL_ADMIN,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('rol');

        $this->assertDatabaseMissing('users', ['email' => 'otro-admin@wally.com']);
    }

    public function test_no_puede_promover_otro_usuario_a_admin(): void
    {
        $admin = $this->admin();
        $usuario = $this->cliente();

        $this->actingAs($admin)->put("/admin/usuarios/{$usuario->id}", [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'rol' => User::ROL_ADMIN,
            'estado' => '1',
        ])->assertSessionHasErrors('rol');

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'rol' => User::ROL_CLIENTE]);
    }

    public function test_la_base_de_datos_impide_un_segundo_admin(): void
    {
        $this->admin();

        $this->expectException(QueryException::class);
        $this->admin();
    }

    public function test_un_admin_puede_editar_un_usuario(): void
    {
        $usuario = $this->cliente();

        $response = $this->actingAs($this->admin())->put("/admin/usuarios/{$usuario->id}", [
            'name' => 'Nombre Actualizado',
            'email' => $usuario->email,
            'telefono' => '71234571',
            'rol' => 'cliente',
            'estado' => '0',
        ]);

        $response->assertRedirect('/admin/usuarios');
        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'name' => 'Nombre Actualizado',
            'estado' => false,
        ]);
    }

    public function test_un_admin_puede_eliminar_un_usuario(): void
    {
        $usuario = $this->cliente();

        $response = $this->actingAs($this->admin())->delete("/admin/usuarios/{$usuario->id}");

        $response->assertRedirect('/admin/usuarios');
        $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
    }

    public function test_un_admin_no_puede_eliminarse_a_si_mismo(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->delete("/admin/usuarios/{$admin->id}");

        $response->assertSessionHasErrors('eliminacion');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_un_usuario_puede_editar_su_perfil(): void
    {
        $usuario = $this->cliente();

        $response = $this->actingAs($usuario)->put('/perfil', [
            'name' => 'Perfil Actualizado',
            'telefono' => '71234572',
            'password' => '',
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'name' => 'Perfil Actualizado',
        ]);
    }

    public function test_un_admin_puede_crear_un_recepcionista(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/usuarios', [
                'name' => 'Personal de Recepción',
                'email' => 'recepcionista@wally.com',
                'telefono' => '70000001',
                'rol' => User::ROL_RECEPCIONISTA,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect('/admin/usuarios');

        $this->assertDatabaseHas('users', [
            'email' => 'recepcionista@wally.com',
            'rol' => User::ROL_RECEPCIONISTA,
        ]);
    }
}
