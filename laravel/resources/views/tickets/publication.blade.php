@extends('layouts.app')
@section('title','QR del ticket #'.$row->venta)
@push('styles')<link rel="stylesheet" href="{{ asset('css/tickets.css') }}?v={{ filemtime(public_path('css/tickets.css')) }}">@endpush
@push('scripts')
<script defer src="{{ asset('js/vendor/qrcode.js') }}"></script>
<script defer src="{{ asset('js/tickets.js') }}?v={{ filemtime(public_path('js/tickets.js')) }}"></script>
@endpush
@section('content')
<section class="panel qr-publication">
    <h1>QR del ticket #{{ $row->venta }}</h1>
    <div data-publication="{{ route('tickets.publication',$row->venta) }}">
        <div data-code></div><p role="status" data-status>Consultando la entrega…</p>
    </div>
    <form method="POST" action="{{ route('tickets.manage',$row->venta) }}">@csrf
        <input type="hidden" name="accion" value="publicar">
        @include('tickets.duration', ['seconds'=>(int)$row->segundos])
        <button class="btn btn-primary">Volver a generar QR</button>
    </form>
    <p>Al generar otro código, el anterior deja de funcionar.</p>
    <a class="btn btn-secondary" href="{{ route('sales.history') }}">Volver al historial de ventas</a>
</section>
@endsection
