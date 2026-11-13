<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Services\Inventory\StockLedger;
use App\Services\Operations\AuditTrail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ProductWriter
{
    public function __construct(private StockLedger $stock, private AuditTrail $audit) {}

    public function save(int $user, array $data, ?int $id = null, ?UploadedFile $image = null): int
    {
        $path = $image?->store('productos', 'local');
        try {
            return DB::transaction(function () use ($user, $data, $id, $path): int {
                $fields = array_intersect_key($data, array_flip(['nombre', 'descripcion', 'id_categoria', 'id_almacen', 'id_proveedor_preferido', 'codigo_barra', 'precio_compra', 'precio_venta', 'stock_minimo', 'imagen_url', 'publicado']));
                $fields['publicado'] = (bool) ($fields['publicado'] ?? false);
                if ($path) {
                    $fields['imagen_path'] = 'storage:'.$path;
                    $fields['imagen_url'] = null;
                }
                if ($id) {
                    DB::table('productos')->where('id_producto', $id)->lockForUpdate()->firstOrFail();
                    DB::table('productos')->where('id_producto', $id)->update($fields);
                } else {
                    $id = DB::table('productos')->insertGetId($fields + ['stock' => 0, 'activo' => 1], 'id_producto');
                    DB::table('productos')->where('id_producto', $id)->update(['slug' => 'producto-'.$id]);
                    $initial = (int) ($data['stock_inicial'] ?? 0);
                    if ($initial > 0) {
                        $this->stock->move($id, (int) $data['id_almacen'], $initial, 'ajuste_positivo', $user, $id);
                    }
                }
                $this->audit->record($user, 'guardar', 'productos', $id, $fields);

                return $id;
            }, 3);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }

    public function state(int $user, int $id, bool $active): void
    {
        DB::transaction(function () use ($user, $id, $active): void {
            DB::table('productos')->where('id_producto', $id)->lockForUpdate()->firstOrFail();
            DB::table('productos')->where('id_producto', $id)->update(['activo' => $active]);
            $this->audit->record($user, 'estado', 'productos', $id, ['activo' => $active]);
        });
    }
}
