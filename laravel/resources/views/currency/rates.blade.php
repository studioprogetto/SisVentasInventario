@extends('layouts.app')
@section('content')
<h1>Tipos de cambio</h1><p>Valor de una unidad de la moneda seleccionada expresado en la moneda principal. Cada venta o compra nueva conserva la tasa aplicada.</p>
@include('currency.rate-list')
@endsection

