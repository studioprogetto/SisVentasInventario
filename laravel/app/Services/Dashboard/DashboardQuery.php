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

        $salesTotal = $this->toFloat((clone $currentSales)->sum('total_base'));
        $previousSalesTotal = $this->toFloat((clone $previousSales)->sum('total_base'));
        $salesMissing = (clone $currentSales)->whereNull('total_base')->count();
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
            ->selectRaw('SUM(v.total_base) as total')
            ->groupByRaw('DATE(v.fecha_venta)')
            ->orderBy('dia')
            ->get();

        $exchangeRates = DB::table('tipo_cambio as tc')
            ->join('tipo_moneda as tm', 'tm.id', '=', 'tc.id_moneda')
            ->where('tc.fecha', '<=', $until)
            ->select('tm.codigo as codigo', 'tc.valor_soles as valor_soles', 'tc.fecha as fecha')
            ->orderByDesc('tc.fecha')
            ->get()
            ->unique('codigo')
            ->take(2)
            ->values();

        $undocumented = (clone $currentSales)->whereNull('total_base')->whereNull('moneda_codigo')
            ->selectRaw('DATE(fecha_venta) as dia, SUM(total_venta) as total')->groupByRaw('DATE(fecha_venta)')->get();
        $chartPoints = $this->normalizeSeries($salesTrend, $since, $until);
        $dailyUnits = DB::table('detalle_ventas as d')->join('ventas as v', 'v.id_venta', '=', 'd.id_venta')
            ->where('v.estado', 'completada')->whereBetween('v.fecha_venta', [$start, $end])->where('d.tipo_item', 'producto')
            ->selectRaw('DATE(v.fecha_venta) as dia, SUM(d.cantidad) as total')->groupByRaw('DATE(v.fecha_venta)')->get();
        $dailyClients = (clone $currentSales)->selectRaw('DATE(fecha_venta) as dia, COUNT(DISTINCT id_cliente) as total')->groupByRaw('DATE(fecha_venta)')->get();
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
            'sales_missing' => $salesMissing,
            'spark_series' => [$chartPoints, $this->normalizeSeries($dailyUnits, $since, $until), $this->normalizeSeries($dailyClients, $since, $until)],
            'sales_count' => $salesCount,
            'products_sold' => (float) $productsSold,
            'customers_served' => (int) $clientsServed,
            'gross_profit' => $grossProfit,
            'profit_missing' => $profitRows->count() - $documented->count(),
            'payments' => $payments,
            'top_products' => $topProducts,
            'stock_by_warehouse' => $warehouseStocks,
            'sales_series' => $chartPoints,
            'legacy_series' => $this->normalizeSeries($undocumented, $since, $until),
            'rates' => $exchangeRates,
            'cards' => $cards,
            'period_label' => Carbon::parse($since)->format('d/m/Y').' — '.Carbon::parse($until)->format('d/m/Y'),
        ];
    }

    private function previousWindow(string $since, string $until): array
    {
        $start = Carbon::parse($since);
        $end = Carbon::parse($until);
        $duration = (int) $start->diffInDays($end, true) + 1;

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
