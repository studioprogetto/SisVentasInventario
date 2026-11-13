<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Producto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ProductImage
{
    public function response(Producto $product): mixed
    {
        $path = (string) $product->getAttribute('imagen_path');
        if (filter_var($path, FILTER_VALIDATE_URL) && in_array(parse_url($path, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return redirect()->away($path);
        }
        if (str_starts_with($path, 'storage:')) {
            $relative = substr($path, 8);
            if (preg_match('#^productos/[a-zA-Z0-9_.-]+$#D', $relative) && Storage::disk('local')->exists($relative)) {
                return response()->file(Storage::disk('local')->path($relative), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public,max-age=0,must-revalidate']);
            }
        } elseif ($path !== '') {
            $legacy = base_path('../app/public/uploads/productos/'.basename(str_replace('\\', '/', $path)));
            if (is_file($legacy) && in_array(strtolower(pathinfo($legacy, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                return response()->file($legacy, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public,max-age=0,must-revalidate']);
            }
        }
        $external = $product->getAttribute('imagen_url');
        if (is_string($external) && filter_var($external, FILTER_VALIDATE_URL) && in_array(parse_url($external, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return redirect()->away($external);
        }
        $barcode = (string) $product->getAttribute('codigo_barra');
        if ($barcode !== '' && preg_match('/^[a-zA-Z0-9_-]+$/D', $barcode)) {
            foreach (['jpg', 'png', 'webp'] as $extension) {
                $legacy = base_path('../../imagenes/'.$barcode.'.'.$extension);
                if (is_file($legacy)) {
                    return response()->file($legacy);
                }
            }
        }
        $legacyName = preg_replace('/\s+/u', '_', mb_strtolower((string) $product->getAttribute('nombre')));
        if ($legacyName !== '' && ! str_contains($legacyName, '/') && ! str_contains($legacyName, '\\') && ! str_contains($legacyName, '..')) {
            $legacy = base_path('../../imagenes/imagenes productos/'.$legacyName.'.jpg');
            if (is_file($legacy)) {
                return response()->file($legacy);
            }
        }

        return $this->brandLogoResponse();
    }

    private function brandLogoResponse(): BinaryFileResponse
    {
        $disk = Storage::disk('public');
        $candidatePaths = [
            $disk->path('branding/logo.png'),
            public_path('img/logo.png'),
            public_path('img/logo.jpg'),
            public_path('img/logo.jpeg'),
            public_path('img/logo.webp'),
        ];

        foreach ($candidatePaths as $candidate) {
            if (is_file($candidate)) {
                return response()->file($candidate, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public,max-age=0,must-revalidate']);
            }
        }

        return response()->file(public_path('img/logo.png'), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public,max-age=0,must-revalidate']);
    }
}
