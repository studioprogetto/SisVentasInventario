<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class ReportQuery
{
    public const TYPES = [
        'ventas' => ['Ventas', 'reportes_ver_ventas'], 'compras' => ['Compras', 'compras_ver_lista'],
        'inventario' => ['Inventario', 'reportes_ver_inventario'], 'movimientos' => ['Movimientos de inventario', 'reportes_ver_inventario'],
        'top-productos' => ['Productos más vendidos', 'reportes_ver_top_productos'], 'stock-bajo' => ['Stock bajo', 'reportes_ver_stock_bajo'],
        'rentabilidad' => ['Rentabilidad documentada', 'reportes_ver_rentabilidad'], 'metodos-pago' => ['Métodos de pago', 'reportes_ver_metodos_pago'],
        'clientes' => ['Clientes', 'clientes_ver_lista'], 'proveedores' => ['Proveedores', 'proveedores_ver_lista'], 'caja' => ['Caja', 'reportes_ver_caja'],
        'saldos' => ['Saldos de clientes y conciliación', 'clientes_ver_lista'],
        'movimientos-caja' => ['Movimientos de caja', 'reportes_ver_caja'],
        'devoluciones' => ['Cambios y devoluciones', 'ventas_ver_listado'],
    ];

    public function build(string $type, string $since, string $until): Builder
    {
        $start = $since.' 00:00:00';
        $end = $until.' 23:59:59';
        $sales = DB::table('ventas as v')->whereBetween('v.fecha_venta', [$start, $end]);

        return match ($type) {
            'ventas' => $sales->leftJoin('clientes as c', 'v.id_cliente', '=', 'c.id_cliente')->leftJoin('usuarios as u', 'u.id_usuario', '=', 'v.id_usuario')
                ->select('v.id_venta as operacion', 'v.fecha_venta as fecha', 'c.nombre_cliente as cliente', 'u.nombre_completo as usuario', 'v.moneda_codigo as moneda', 'v.total_venta as importe', 'v.metodo_pago as pago', 'v.estado')->orderByDesc('v.id_venta'),
            'compras' => DB::table('compras as c')->leftJoin('proveedores as p', 'p.id_proveedor', '=', 'c.id_proveedor')->whereBetween('c.fecha_compra', [$start, $end])->select('c.id_compra as operacion', 'c.fecha_compra as fecha', 'p.nombre_proveedor as proveedor', 'c.moneda_codigo as moneda', 'c.total_compra as importe', 'c.estado')->orderByDesc('c.id_compra'),
            'inventario', 'stock-bajo' => DB::table('productos')->where('activo', 1)->when($type === 'stock-bajo', fn ($query) => $query->whereColumn('stock', '<=', 'stock_minimo'))->select('id_producto as producto', 'nombre', 'stock', 'stock_minimo as minimo', 'precio_compra as costo_actual')->selectRaw('ROUND(stock * precio_compra, 2) as valor_actual')->orderBy('nombre'),
            'movimientos' => DB::table('movimientos_inventario as m')->join('productos as p', 'm.id_producto', '=', 'p.id_producto')->whereBetween('m.fecha', [$start, $end])->select('m.id_movimiento as movimiento', 'm.fecha', 'p.nombre as producto', 'm.id_almacen as almacen', 'm.tipo_movimiento as causa', 'm.cantidad', 'm.stock_anterior as antes', 'm.stock_nuevo as despues', 'm.id_usuario as usuario', 'm.referencia_id as referencia')->orderByDesc('m.id_movimiento'),
            'metodos-pago' => $sales->groupBy('v.moneda_codigo', 'v.metodo_pago')->select('v.moneda_codigo as moneda', 'v.metodo_pago as pago')->selectRaw('COUNT(*) as operaciones, SUM(v.total_venta) as importe, SUM(v.saldo_utilizado) as saldo_utilizado, SUM(v.cobro_base) as cobro_base')->orderBy('v.metodo_pago'),
            'clientes' => DB::table('clientes')->select('id_cliente as cliente', 'nombre_cliente as nombre', 'documento_identidad as documento', 'telefono', 'email', 'total_compras as compras', 'sellos')->orderBy('nombre_cliente'),
            'proveedores' => DB::table('proveedores')->select('id_proveedor as proveedor', 'nombre_proveedor as nombre', 'ruc', 'telefono', 'email', 'activo')->orderBy('nombre_proveedor'),
            'caja' => DB::table('turnos_caja')->whereBetween('fecha_apertura', [$start, $end])->select('id_turno as turno', 'id_usuario as usuario', 'fecha_apertura as apertura', 'fecha_cierre as cierre', 'monto_inicial as inicial', 'monto_final_sistema as esperado', 'monto_final_real as declarado', 'diferencia', 'estado')->orderByDesc('id_turno'),
            'saldos' => DB::table('saldos_clientes as s')->leftJoin('clientes as c', 'c.id_cliente', '=', 's.id_cliente')->leftJoin('tipo_moneda as m', 'm.id', '=', 's.id_moneda')->select('s.id_saldo as registro', 'c.nombre_cliente as cliente', 's.saldo', 's.estado', 'm.codigo as moneda', 's.fecha_actualizacion as fecha')->orderBy('s.id_saldo'),
            'movimientos-caja' => DB::table('movimientos_caja')->whereBetween('fecha', [$start, $end])->select('id_movimiento_caja as movimiento', 'id_turno as turno', 'id_usuario as usuario', 'tipo_movimiento as causa', 'monto as importe', 'descripcion', 'fecha')->orderByDesc('id_movimiento_caja'),
            'devoluciones' => DB::table('cambios_ventas')->whereBetween('fecha_cambio', [$start, $end])->select('id_cambio as cambio', 'id_venta_original as venta_original', 'id_venta_nueva as venta_nueva', 'id_usuario as usuario', 'tipo', 'monto_saldo as importe', 'estado', 'observacion', 'fecha_cambio as fecha')->orderByDesc('id_cambio'),
            'top-productos', 'rentabilidad' => $this->productSales($type, $start, $end),
            default => abort(404),
        };
    }

    private function productSales(string $type, string $start, string $end): Builder
    {
        $returned = DB::table('detalles_cambio as dc')->join('cambios_ventas as cv', 'cv.id_cambio', '=', 'dc.id_cambio')->where('dc.tipo', 'devolucion')->where('cv.estado', 'completado')
            ->groupBy('dc.id_detalle_venta')->select('dc.id_detalle_venta')->selectRaw('SUM(dc.cantidad) as cantidad');
        $query = DB::table('detalle_ventas as d')->join('ventas as v', 'v.id_venta', '=', 'd.id_venta')->join('productos as p', 'p.id_producto', '=', 'd.id_producto')
            ->leftJoinSub($returned, 'r', fn ($join) => $join->on('r.id_detalle_venta', '=', 'd.id_detalle_venta'))
            ->whereBetween('v.fecha_venta', [$start, $end])->whereIn('v.estado', ['completada', 'parcialmente_devuelta'])->where('d.tipo_item', 'producto');
        if ($type === 'top-productos') {
            return $query->groupBy('p.id_producto', 'p.nombre')->select('p.id_producto as producto', 'p.nombre')->selectRaw('SUM(d.cantidad - COALESCE(r.cantidad,0)) as unidades_netas')->orderByDesc('unidades_netas');
        }

        return $query->select('v.id_venta as venta', 'p.nombre as producto', 'v.moneda_codigo as moneda')->selectRaw('d.cantidad-COALESCE(r.cantidad,0) as unidades_netas')
            ->selectRaw('CASE WHEN d.importe_neto IS NULL OR d.costo_unitario_base IS NULL OR v.tipo_cambio_aplicado IS NULL THEN NULL ELSE ROUND((d.importe_neto*v.tipo_cambio_aplicado/d.cantidad-d.costo_unitario_base)*(d.cantidad-COALESCE(r.cantidad,0)),2) END as ganancia_base_documentada')->orderByDesc('v.id_venta');
    }
}
