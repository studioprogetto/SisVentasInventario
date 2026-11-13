<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Producto;
use Picqer\Barcode\BarcodeGeneratorSVG;

final class LabelSheet
{
    public function labels(array $ids): array
    {
        $generator = new BarcodeGeneratorSVG;

        return Producto::query()->whereIn('id_producto', $ids)->orderBy('nombre')->get()->map(function ($product) use ($generator): array {
            $code = (string) $product->getAttribute('codigo_barra');
            $barcode = $code !== '' && preg_match('/^[\x20-\x7E]+$/D', $code) ? $generator->getBarcode($code, $generator::TYPE_CODE_128) : null;

            return ['nombre' => $product->getAttribute('nombre'), 'precio' => $product->getAttribute('precio_venta'), 'codigo' => $code, 'barcode' => $barcode];
        })->all();
    }
}
