<?php

use App\Http\Controllers\TicketDeliveryController as Tickets;
use App\Http\Controllers\TicketDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'can:ventas_crear'])->group(function () {
    Route::post('tickets/documento', [TicketDocumentController::class, 'attach'])->middleware(['can:admin_gestionar_roles', 'writable'])->name('tickets.attachForm');
    Route::get('tickets/venta/{sale}', [Tickets::class, 'publication'])->whereNumber('sale')->name('tickets.publication');
    Route::get('tickets', [Tickets::class, 'index'])->name('tickets.center');
    Route::post('tickets/venta/{sale}', [Tickets::class, 'manage'])->whereNumber('sale')->middleware('writable')->name('tickets.manage');
    Route::get('tickets/pantalla', [Tickets::class, 'screen'])->name('tickets.screen');
    Route::get('tickets/publicacion', [Tickets::class, 'state'])->name('tickets.state');
});

Route::get('ticket/pantalla/{token}', [Tickets::class, 'customerScreen'])->where('token', '[a-f0-9]{64}')->name('tickets.customerScreen');
Route::get('ticket/pantalla/{token}/estado', [Tickets::class, 'customerScreen'])->where('token', '[a-f0-9]{64}')->name('tickets.screenData');

Route::middleware('throttle:60,1')->group(function () {
    Route::get('ticket/portal', [Tickets::class, 'portal'])->name('tickets.portal');
    Route::get('ticket/acceso/{token}', [Tickets::class, 'confirm'])->where('token', '[a-f0-9]{64}')->name('tickets.confirm');
    Route::post('ticket/acceso/{token}', [Tickets::class, 'claim'])->where('token', '[a-f0-9]{64}')->name('tickets.claim');
    Route::get('ticket/recibido', [Tickets::class, 'receipt'])->name('tickets.receipt');
    Route::get('ticket/documento/{document}', [Tickets::class, 'document'])->whereNumber('document')->name('tickets.document');
    Route::get('ticket/recibido/pdf', [Tickets::class, 'receipt'])->name('tickets.pdf');
});
