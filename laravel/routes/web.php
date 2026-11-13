<?php

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Categorias\CategoriaController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'home');
Route::middleware('guest')->group(function (): void {
    Route::get('auth/login', [SessionController::class, 'create'])->name('login');
    Route::post('auth/login', [SessionController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('auth/logout', [SessionController::class, 'destroy'])->name('logout');
    Route::get('home', DashboardController::class)->middleware('can:dashboard_ver')->name('home');
    Route::get('categoria', [CategoriaController::class, 'index'])->name('categorias.index');
    Route::middleware('writable')->group(function (): void {
        Route::post('categoria/guardar', [CategoriaController::class, 'store'])->name('categorias.store');
        Route::put('categoria/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
        Route::patch('categoria/{categoria}/estado', [CategoriaController::class, 'state'])->name('categorias.state');
    });
});

Route::middleware(['auth', 'active'])->group(__DIR__.'/modules.php');
require __DIR__.'/catalog.php';
