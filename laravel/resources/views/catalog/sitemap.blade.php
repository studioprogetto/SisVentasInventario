{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>{{ route('catalog.index') }}</loc></url>@foreach($products as $product)<url><loc>{{ route('catalog.product',$product->slug) }}</loc></url>@endforeach</urlset>
