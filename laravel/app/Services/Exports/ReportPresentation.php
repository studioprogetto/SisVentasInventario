<?php

declare(strict_types=1);

namespace App\Services\Exports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ReportPresentation
{
    public static function logo(): string
    {
        $disk = Storage::disk('public');

        return $disk->exists('branding/logo.png') ? $disk->path('branding/logo.png') : public_path('img/logo.png');
    }

    /** Count filtered rows by one documented dimension without summing different currencies. */
    public static function distribution(Builder $query, string $type): array
    {
        $field = match ($type) {
            'ventas' => 'pago',
            'compras', 'caja', 'saldos', 'devoluciones' => 'estado',
            'movimientos', 'movimientos-caja' => 'causa',
            'metodos-pago' => 'pago',
            'proveedores' => 'activo',
            'clientes' => 'sellos',
            'inventario', 'stock-bajo' => 'CASE WHEN stock <= 0 THEN \'Agotado\' WHEN stock <= minimo THEN \'En mínimo\' ELSE \'Sobre mínimo\' END',
            'top-productos' => 'nombre',
            'rentabilidad' => 'moneda',
        };
        $counts = DB::query()->fromSub(clone $query, 'detalle')->selectRaw("$field AS etiqueta, COUNT(*) AS registros")
            ->groupByRaw($field)->orderByDesc('registros')->orderBy('etiqueta')->limit(6)->get();
        if ($type === 'top-productos') {
            return (clone $query)->pluck('unidades_netas', 'nombre')->all();
        }

        return $counts->mapWithKeys(fn ($row) => [($type === 'proveedores' ? ($row->etiqueta ? 'Activos' : 'Inactivos') : ($row->etiqueta ?? 'Sin documentar')) => (int) $row->registros])->all();
    }
}
