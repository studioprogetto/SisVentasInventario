@extends('layouts.app')
@section('content')
<h1>Etiquetas</h1><button type="button" data-print>Imprimir / guardar PDF</button><div class="cards">@foreach($labels as $label)<article class="card"><strong>{{ $label['nombre'] }}</strong><p>S/ {{ $label['precio'] }}</p>@if($label['barcode']){!! $label['barcode'] !!}<p>{{ $label['codigo'] }}</p>@else<p>Sin código de barras registrado</p>@endif</article>@endforeach</div>
@endsection
