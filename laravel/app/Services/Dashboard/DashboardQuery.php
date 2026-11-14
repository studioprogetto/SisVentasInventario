<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Services\Reports\ReportQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class DashboardQuery
{
    public function summary(?string $since = null, ?string $until = null): array
    {
        $since ??= now()->startOfMonth()->toDateString();
        $until ??= now()->toDateString();
        $start = $since.' 00:00:00';
        $end = $until.' 23:59:59';

        $currentSales = DB::table('ventas')->where('estado', 'completada')->whereBetween('fecha_venta', [$start, $end]);
        $previousWindow = $this->previousWindow($since, $until);
        $previousSales = DB::table('ventas')->where('estado', 'completada')->whereBetween('fecha_venta', [$previousWindow['start'], $previousWindow['end']]);

        $salesTotal = $this->toFloat((clone $currentSales)->sum('total_venta'));
        $previousSalesTotal = $this->toFloat((clone $previousSales)->sum('total_venta'));
        $salesCount = (clone $currentSales)->count();
        $clientsServed = (clone $currentSales)->whereNotNull('id_cliente')->distinct()->count('id_cliente');

        $productsSold = DB::table('detalle_ventas as d')
            ->join('ventas as v', 'v.id_venta', '=', 'd.id_venta')
            ->where('v.estado', 'completada')
            ->whereBetween('v.fecha_venta', [$start, $end])
            ->where('d.tipo_item', 'producto')
            ->sum('d.cantidad');

        $reports = new ReportQuery;
        $profitRows = $reports->build('rentabilidad', $since, $until)->get();
        $documented = $profitRows->whereNotNull('ganancia_base_documentada');
        $grossProfit = $documented->isEmpty() ? null : (float) $documented->sum('ganancia_base_documentada');
        $stockTotals = DB::table('existencias')->select('id_producto')->selectRaw('SUM(stock) as stock')->groupBy('id_producto');
        $inventory = DB::table('productos as p')->leftJoinSub($stockTotals, 'i', 'i.id_producto', '=', 'p.id_producto')->where('p.activo', true);
        $lowStock = (clone $inventory)->whereRaw('COALESCE(i.stock, 0) <= p.stock_minimo');
        $inventoryHealth = (clone $inventory)->selectRaw('COUNT(*) as total, SUM(CASE WHEN COALESCE(i.stock, 0) <= 0 THEN 1 ELSE 0 END) as agotados, SUM(CASE WHEN COALESCE(i.stock, 0) > 0 AND COALESCE(i.stock, 0) <= p.stock_minimo THEN 1 ELSE 0 END) as reposicion, SUM(CASE WHEN COALESCE(i.stock, 0) > p.stock_minimo THEN 1 ELSE 0 END) as suficiente')->first();
        $payments = (clone $currentSales)->groupBy('moneda_codigo', 'metodo_pago')->select('moneda_codigo', 'metodo_pago')->selectRaw('COUNT(*) as operaciones, SUM(cobro_base) as cobro_base')->get();

        $topProducts = DB::table('detalle_ventas as d')
            ->join('ventas as v', 'v.id_venta', '=', 'd.id_venta')
            ->join('productos as p', 'p.id_producto', '=', 'd.id_producto')
            ->where('v.estado', 'completada')
            ->whereBetween('v.fecha_venta', [$start, $end])
            ->where('d.tipo_item', 'producto')
            ->groupBy('p.id_producto', 'p.nombre')
            ->select('p.id_producto as id', 'p.nombre as nombre', DB::raw('SUM(d.cantidad) as unidades'), DB::raw('ROUND(SUM(d.importe_neto), 2) as importe'))
            ->orderByDesc('unidades')
            ->limit(5)
            ->get();

        $warehouseStocks = DB::table('existencias as e')
            ->join('productos as p', 'p.id_producto', '=', 'e.id_producto')
            ->join('almacenes as a', 'a.id', '=', 'e.id_almacen')
            ->select('a.nombre as almacen', DB::raw('COALESCE(SUM(e.stock), 0) as stock_total'))
            ->where('p.activo', true)
            ->where('a.activo', true)
            ->groupBy('a.id', 'a.nombre')
            ->orderByDesc('stock_total')
            ->get();

        $salesTrend = DB::table('ventas as v')
            ->where('v.estado', 'completada')
            ->whereBetween('v.fecha_venta', [$start, $end])
            ->selectRaw('DATE(v.fecha_venta) as dia')
            ->selectRaw('SUM(v.total_venta) as total')
            ->groupByRaw('DATE(v.fecha_venta)')
            ->orderBy('dia')
            ->get();

        $latestSales = DB::table('ventas as v')
            ->leftJoin('clientes as c', 'c.id_cliente', '=', 'v.id_cliente')
            ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'v.id_usuario')
            ->whereBetween('v.fecha_venta', [$start, $end])
            ->select('v.id_venta as id_venta', 'v.fecha_venta as fecha_venta', DB::raw('COALESCE(c.nombre_cliente, "Venta sin cliente") as cliente'), 'v.total_venta as total_venta', 'v.estado', 'u.nombre_completo as usuario')
            ->orderByDesc('v.fecha_venta')
            ->limit(5)
            ->get();

        $operations = [
            'compras' => (float) DB::table('compras')->where('estado', 'recibida')->whereBetween('fecha_compra', [$start, $end])->sum('total_compra'),
            'devoluciones' => (float) DB::table('cambios_ventas')->whereBetween('fecha_cambio', [$start, $end])->sum('monto_saldo'),
            'transferencias' => DB::table('movimientos_inventario')->whereBetween('fecha', [$start, $end])->where('tipo_movimiento', 'transferencia_salida')->count() + DB::table('movimientos_inventario')->whereBetween('fecha', [$start, $end])->where('tipo_movimiento', 'transferencia_entrada')->count(),
        ];

        $exchangeRates = DB::table('tipo_cambio as tc')
            ->join('tipo_moneda as tm', 'tm.id', '=', 'tc.id_moneda')
            ->where('tc.fecha', '<=', $until)
            ->select('tm.codigo as codigo', 'tc.valor_soles as valor_soles', 'tc.fecha as fecha')
            ->orderByDesc('tc.fecha')
            ->get()
            ->unique('codigo')
            ->take(2)
            ->values();

        $chartPoints = $this->normalizeSeries($salesTrend, $since, $until);
        $cards = [
            [
                'label' => 'Ventas del periodo',
                'value' => $this->money($salesTotal),
                'trend' => $this->trendDelta($salesTotal, $previousSalesTotal),
                'tone' => 'primary',
                'icon' => 'cart',
                'trend_suffix' => 'vs. período previo',
            ],
            [
                'label' => 'Productos vendidos',
                'value' => number_format((float) $productsSold, 0, ',', '.'),
                'trend' => $this->trendDelta((float) $productsSold, (float) DB::table('detalle_ventas as d')->join('ventas as v', 'v.id_venta', '=', 'd.id_venta')->where('v.estado', 'completada')->whereBetween('v.fecha_venta', [$previousWindow['start'], $previousWindow['end']])->where('d.tipo_item', 'producto')->sum('d.cantidad')),
                'tone' => 'purple',
                'icon' => 'box',
                'trend_suffix' => 'vs. período previo',
            ],
            [
                'label' => 'Clientes atendidos',
                'value' => number_format((int) $clientsServed, 0, ',', '.'),
                'trend' => $this->trendDelta((float) $clientsServed, (float) (clone $previousSales)->whereNotNull('id_cliente')->distinct()->count('id_cliente')),
                'tone' => 'blue',
                'icon' => 'users',
                'trend_suffix' => 'vs. período previo',
            ],
            [
                'label' => 'Ganancia base documentada',
                'value' => $grossProfit === null ? 'No disponible' : $this->money($grossProfit),
                'trend' => null,
                'tone' => 'amber',
                'icon' => 'wallet',
                'trend_suffix' => 'vs. período previo',
            ],
        ];

        return [
            'since' => $since,
            'until' => $until,
            'sales_total' => $salesTotal,
            'sales_count' => $salesCount,
            'products_sold' => (float) $productsSold,
            'customers_served' => (int) $clientsServed,
            'gross_profit' => $grossProfit,
            'profit_missing' => $profitRows->count() - $documented->count(),
            'payments' => $payments,
            'inventory_health' => $inventoryHealth,
            'low_stock' => (clone $lowStock)->count(),
            'out_of_stock' => (int) ($inventoryHealth->agotados ?? 0),
            'inventory_alerts' => (clone $lowStock)->select('p.id_producto', 'p.nombre', 'p.stock_minimo')->selectRaw('COALESCE(i.stock, 0) as stock')->orderBy('stock')->limit(5)->get(),
            'top_products' => $topProducts,
            'stock_by_warehouse' => $warehouseStocks,
            'sales_series' => $chartPoints,
            'latest_sales' => $latestSales,
            'operations' => $operations,
            'rates' => $exchangeRates,
            'cards' => $cards,
            'period_label' => Carbon::parse($since)->format('d/m/Y').' — '.Carbon::parse($until)->format('d/m/Y'),
        ];
    }

    private function previousWindow(string $since, string $until): array
    {
        $start = Carbon::parse($since);
        $end = Carbon::parse($until);
        $duration = $end->diffInDays($start) + 1;

        return [
            'start' => $start->copy()->subDays($duration)->toDateString().' 00:00:00',
            'end' => $start->copy()->subDay()->toDateString().' 23:59:59',
        ];
    }

    private function normalizeSeries($series, string $since, string $until): array
    {
        $totals = $series->keyBy('dia');
        $points = [];
        for ($day = Carbon::parse($since); $day->lte(Carbon::parse($until)); $day->addDay()) {
            $points[] = ['label' => $day->format('d/m/Y'), 'value' => (float) ($totals->get($day->toDateString())->total ?? 0)];
        }

        return $points;
    }

    private function trendDelta(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? null : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function money(float $amount): string
    {
        return 'S/ '.number_format($amount, 2, ',', '.');
    }

    private function toFloat(mixed $value): float
    {
        return (float) ($value ?? 0);
    }
}
