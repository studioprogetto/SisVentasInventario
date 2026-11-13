<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Reports\ReportQuery;
use Tests\Support\CommercialTestCase;

final class AdministrativePagesTest extends CommercialTestCase
{
    public function test_all_sixteen_menu_pages_render_with_real_schema_and_permissions(): void
    {
        $all = ['dashboard_ver', 'categorias_ver_lista', 'categorias_crear_editar', 'clientes_ver_lista', 'clientes_crear_editar', 'proveedores_ver_lista', 'almacenes_gestionar', 'servicios_gestionar', 'tipomoneda_gestionar', 'usuarios_ver_lista', 'usuarios_crear_editar', 'admin_gestionar_roles', 'tipocambio_ver_lista', 'tipocambio_gestionar', 'backups_gestionar'];
        $user = $this->userWithPermissions(array_unique(array_merge($all, array_column(ReportQuery::TYPES, 1), ['ventas_crear', 'ventas_ver_listado', 'productos_ver_lista', 'transferencias_crear', 'compras_ver_lista', 'caja_gestionar'])));
        $this->actingAs($user);
        foreach (['home', 'venta', 'venta/historial', 'producto', 'servicio', 'categoria', 'almacen', 'transferencia', 'compra', 'proveedor', 'cliente', 'caja', 'reporte', 'usuario', 'tipocambio', 'tipomoneda'] as $path) {
            $this->get('/'.$path)->assertOk();
        }
        foreach (array_keys(ReportQuery::TYPES) as $type) {
            $this->get('/reporte/'.$type)->assertOk();
        }
    }

    public function test_operation_endpoints_validate_and_authorize_in_backend(): void
    {
        $this->actingAs($this->operator);
        $this->postJson('/venta/guardar', $this->cart(['carrito' => [['tipo' => 'producto', 'id' => $this->product, 'cantidad' => -1]]]))->assertUnprocessable();
        $this->postJson('/venta/guardar', $this->cart())->assertUnprocessable();
        $this->get('/cliente')->assertForbidden();
        $this->get('/caja/backups')->assertForbidden();
        $this->get('/venta/buscar?term=Pap')->assertOk()->assertJsonFragment(['nombre' => 'Papel']);
    }
}
