<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Support\BusinessTestCase;

final class CategoriaArchitectureTest extends BusinessTestCase
{
    public function test_policy_blocks_authenticated_user_without_permission(): void
    {
        $this->actingAs($this->userWithPermissions())->get('/categoria')->assertForbidden();
        $this->post('/categoria/guardar', ['nombre' => 'Prohibida'])->assertForbidden();
        $this->assertDatabaseMissing('categorias', ['nombre' => 'Prohibida']);
    }

    public function test_real_permission_allows_validated_create_and_update_without_extra_fields(): void
    {
        $this->actingAs($this->userWithPermissions(['categorias_ver_lista', 'categorias_crear_editar']));
        $this->post('/categoria/guardar', ['nombre' => '  Arquitectura  ', 'activo' => 0])->assertRedirect(route('categorias.index'));
        $this->assertDatabaseHas('categorias', ['nombre' => 'Arquitectura', 'activo' => 1]);
        $id = DB::table('categorias')->value('id_categoria');
        $this->put('/categoria/'.$id, ['nombre' => 'Diseño'])->assertRedirect();
        $this->assertDatabaseHas('categorias', ['id_categoria' => $id, 'nombre' => 'Diseño']);
        $this->get('/categoria')->assertOk()->assertSee('Diseño');
    }

    public function test_duplicate_blank_and_array_names_are_rejected(): void
    {
        $this->actingAs($this->userWithPermissions(['categorias_crear_editar']));
        DB::table('categorias')->insert(['nombre' => 'Existente']);
        foreach (['Existente', '   ', ['invalid']] as $name) {
            $this->postJson('/categoria/guardar', ['nombre' => $name])->assertUnprocessable()->assertJsonValidationErrors('nombre');
        }
        $this->assertSame(1, DB::table('categorias')->count());
    }

    public function test_read_only_mode_prevents_writes_and_get_does_not_mutate(): void
    {
        $this->actingAs($this->userWithPermissions(['categorias_crear_editar']));
        config(['migration.read_only' => true]);
        $this->post('/categoria/guardar', ['nombre' => 'No escribir'])->assertStatus(423);
        $this->get('/categoria/guardar')->assertStatus(405);
        $this->assertSame(0, DB::table('categorias')->count());
    }

    public function test_category_name_is_escaped_when_rendered(): void
    {
        DB::table('categorias')->insert(['nombre' => '<script>alert(1)</script>']);
        $this->actingAs($this->userWithPermissions(['categorias_ver_lista']))->get('/categoria')
            ->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
}
