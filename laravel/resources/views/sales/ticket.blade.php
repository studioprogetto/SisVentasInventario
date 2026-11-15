@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/receipt.css') }}">
@endpush
@section('content')
<div class="ticket-shell">
    <div class="ticket-paper" aria-label="Comprobante de venta">
        @include('sales.partials.receipt')
        <div class="ticket-actions" aria-label="Acciones del ticket">
            <button type="button" class="btn btn-primary" data-print-ticket>Imprimir Ticket</button>
            <button type="button" class="btn btn-success" data-save-pdf>Guardar en PDF</button>
            <a class="btn btn-warning" href="{{ route('sales.pos') }}">Volver al POS</a>
            <a class="btn btn-success" href="{{ route('sales.history') }}">Ver Historial</a>
        </div>
    </div>
</div>
@endsection
