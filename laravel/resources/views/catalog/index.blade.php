@extends('catalog.layout')
@section('content')
<h1>Catálogo de productos</h1>
<form method="GET" class="filters">
    <label>Buscar producto<input name="q" value="{{ request('q') }}" maxlength="100"></label>
    <label>Categoría<select name="categoria"><option value="">Todas</option>@foreach($categories as $category)<option value="{{ $category->id_categoria }}" @selected(request('categoria') == $category->id_categoria)>{{ $category->nombre }}</option>@endforeach</select></label>
    <label>Precio mínimo<input name="minimo" type="number" min="0" step="0.01" value="{{ request('minimo') }}"></label>
    <label>Precio máximo<input name="maximo" type="number" min="0" step="0.01" value="{{ request('maximo') }}"></label>
    <label>Orden<select name="orden"><option value="nombre">Nombre</option><option value="precio_asc" @selected(request('orden') === 'precio_asc')>Menor precio</option><option value="precio_desc" @selected(request('orden') === 'precio_desc')>Mayor precio</option></select></label>
    <button>Buscar</button>
</form>
<div data-search-results>
<p>{{ $products->total() }} productos encontrados.</p>
<div class="cards">
    @forelse($products as $product)
        @php($stockTotal = (int) ($product->stock_total ?? 0))
        <article class="card">
            <a href="{{ route('catalog.product', $product->slug) }}"><img src="{{ route('catalog.image', $product->id_producto) }}" alt="{{ $product->nombre }}" width="320" height="240" loading="lazy" style="width:100%;object-fit:contain"><h2>{{ $product->nombre }}</h2></a>
            <p>{{ $product->categoria->nombre }}</p>
            <p><strong>S/ {{ $product->precio_venta }}</strong></p>
            <p>{{ $stockTotal > 0 ? 'Disponible' : 'Agotado' }}</p>
        </article>
    @empty
        <p>No hay productos publicados que coincidan con la búsqueda.</p>
    @endforelse
</div>
{{ $products->links('vendor.pagination.tailwind') }}
</div>
@endsection
