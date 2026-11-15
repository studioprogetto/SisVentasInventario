<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Services\Queries\BackOfficeQueries;
use App\Services\Queries\PosQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PageController extends Controller
{
    public function pos(PosQuery $queries): mixed
    {
        $lookups = $queries->lookups();
        $currency = $lookups['currencies']->firstWhere('id', config('business.operating_currency_id')) ?? $lookups['currencies']->first();
        return view('sales.pos', $lookups + ['selectedCurrency' => $currency?->id, 'catalog' => $queries->items(['id_moneda' => $currency?->id])]);
    }

    public function history(Request $request, BackOfficeQueries $queries): mixed
    {
        $filters = $request->validate(['desde' => ['nullable', 'date_format:Y-m-d'], 'hasta' => ['nullable', 'date_format:Y-m-d'], 'id' => ['nullable', 'integer'], 'id_cliente' => ['nullable', 'integer'], 'id_usuario' => ['nullable', 'integer'], 'metodo_pago' => ['nullable', 'string', 'max:30'], 'estado' => ['nullable', 'string', 'max:40']]);
        $filters += $request->validate(['cliente' => ['nullable', 'string', 'max:150'], 'usuario' => ['nullable', 'string', 'max:100'], 'id_moneda' => ['nullable', 'integer']]);

        return view('sales.history', ['sales' => $queries->sales($filters)]);
    }

    public function sale(Request $request, BackOfficeQueries $queries, int $id): mixed
    {
        $sale = $queries->sale($id);
        abort_unless($request->user()->can('ventas_ver_listado') || ($request->user()->can('ventas_crear') && (int) $sale->id_usuario === (int) $request->user()->getKey()), 403);

        return $request->expectsJson() ? response()->json($sale) : view('sales.ticket', compact('sale'));
    }

    public function exchanges(Request $request, BackOfficeQueries $queries): mixed
    {
        abort_unless(Gate::any(['ventas_devolver', 'solicitar_devolucion']), 403);
        $ticket = $request->validate(['ticket' => ['nullable', 'integer', 'min:1']])['ticket'] ?? null;
        $sale = null;
        $lines = collect();
        if ($ticket && \App\Models\Venta::whereKey($ticket)->exists()) {
            $sale = $queries->sale((int) $ticket);
            abort_unless($request->user()->can('ventas_ver_listado') || (int) $sale->id_usuario === (int) $request->user()->getKey(), 403);
            if (in_array($sale->estado, ['completada','parcialmente_devuelta'], true)
                && now()->lte(\Illuminate\Support\Carbon::parse($sale->fecha_venta)->addDays(2))) {
                $returned = $sale->cambios->where('estado', 'completado')->flatMap->detalles
                    ->where('tipo', 'devolucion')->groupBy('id_detalle_venta');
                $lines = $sale->detalles->filter(function ($line) use ($returned) {
                    $line->disponibles = $line->cantidad - ($returned->get($line->id_detalle_venta)?->sum('cantidad') ?? 0);
                    return $line->id_producto && $line->id_almacen && $line->importe_neto !== null && $line->disponibles > 0;
                });
            }
        }

        return view('sales.exchanges', compact('sale', 'lines') + [
            'replacementProducts' => $lines->isEmpty() ? [] : \App\Models\Producto::where('activo', true)->orderBy('nombre')->get(['id_producto','nombre']),
            'holders' => $lines->isEmpty() || $sale->id_cliente ? [] : \App\Models\Cliente::where('activo', true)->orderBy('nombre_cliente')->get(['id_cliente','nombre_cliente']),
        ]);
    }

    public function search(Request $request, BackOfficeQueries $queries): mixed
    {
        abort_unless(Gate::any(['ventas_crear', 'compras_crear', 'transferencias_crear', 'productos_ver_lista']), 403);
        $data = $request->validate(['term' => ['nullable', 'string', 'max:100'], 'categoria' => ['nullable', 'integer']]);

        return response()->json($queries->search(trim($data['term'] ?? ''), isset($data['categoria']) ? (int) $data['categoria'] : null));
    }

    public function clients(Request $request, BackOfficeQueries $queries): mixed
    {
        abort_unless(Gate::any(['ventas_crear', 'clientes_ver_lista']), 403);
        $data = $request->validate(['term' => ['required', 'string', 'min:2', 'max:100']]);

        return response()->json($queries->clients($data['term']));
    }

    public function purchases(BackOfficeQueries $queries): mixed
    {
        return view('purchases.index', ['purchases' => $queries->purchases()] + $queries->lookups());
    }

    public function transfers(BackOfficeQueries $queries): mixed
    {
        return view('transfers.index', ['transfers' => $queries->transfers()] + $queries->lookups());
    }

    public function cashReport(Request $request, BackOfficeQueries $queries): mixed
    {
        return view('cash.report', $queries->cash((int) $request->user()->getKey()));
    }

    public function cash(Request $request, BackOfficeQueries $queries): mixed
    {
        return view('cash.index', $queries->cash((int) $request->user()->getKey(), false));
    }
}
