<?php

use App\Http\Controllers\Catalog\CatalogController;
use App\Http\Controllers\Users\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:120,1')->group(function (): void {
    Route::get('catalogo', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('catalogo/productos/{slug}', [CatalogController::class, 'show'])->name('catalog.product');
    Route::get('catalogo/imagenes/{id}', [CatalogController::class, 'image'])->whereNumber('id')->name('catalog.image');
    Route::get('api/catalogo/productos', [CatalogController::class, 'index'])->name('catalog.api.products');
    Route::get('api/catalogo/productos/{slug}', [CatalogController::class, 'show'])->name('catalog.api.product');
    Route::get('api/catalogo/categorias', [CatalogController::class, 'categories'])->name('catalog.api.categories');
});
Route::get('sitemap.xml', [CatalogController::class, 'sitemap'])->name('catalog.sitemap');
Route::get('brand/logo', [ProfileController::class, 'logo'])->name('brand.logo');
