@extends('catalog.layout')
@section('title', $product->nombre.' — '.config('business.name', 'Studio & Progetto'))
@section('description', Illuminate\Support\Str::limit(strip_tags($product->descripcion ?? $product->nombre), 160))
@section('content')
@php($stockTotal = (int) ($product->stock_total ?? 0))
<a href="{{ route('catalog.index', ['categoria' => $product->id_categoria]) }}">{{ $product->categoria->nombre }}</a>
<div class="cards">
    <img src="{{ route('catalog.image', $product->id_producto) }}" alt="{{ $product->nombre }}" width="480" height="360" style="width:100%;object-fit:contain">
    <article>
        <h1>{{ $product->nombre }}</h1>
        <p>{{ $product->descripcion }}</p>
        <p class="metric">S/ {{ $product->precio_venta }}</p>
        <p>{{ $stockTotal > 0 ? 'Disponible' : 'Agotado' }}</p>
    </article>
</div>
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $product->nombre, 'description' => $product->descripcion, 'image' => route('catalog.image', $product->id_producto), 'offers' => ['@type' => 'Offer', 'priceCurrency' => 'PEN', 'price' => $product->precio_venta, 'availability' => 'https://schema.org/'.($stockTotal > 0 ? 'InStock' : 'OutOfStock'), 'url' => url()->current()]], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection
