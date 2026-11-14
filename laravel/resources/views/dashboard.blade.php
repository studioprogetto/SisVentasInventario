@extends('layouts.app')
@section('title', 'Actividad de la agencia')
@section('content')
<section class="categories-page">
    <header class="cat-heading"><div><small>AGENCIA DIGITAL · DATOS DE EJEMPLO</small><h1>Actividad de noviembre</h1><p>Del 1 al 30 de noviembre de 2026</p></div>
        <a class="btn btn-secondary" href="{{ route('home', ['operativo' => 1]) }}">Ver datos reales</a>
    </header>
    <div class="agency-kpis">
        @foreach(['Ingresos del mes' => 'S/ 18 450', 'Proyectos / productos vendidos' => '64', 'Stock crítico' => '3 artículos', 'Clientes activos' => '18'] as $label => $value)
            <article class="cat-surface cat-padding" data-agency-kpi><small>{{ $label }}</small><strong>{{ $value }}</strong></article>
        @endforeach
    </div>
    <section class="cat-surface cat-padding"><h2>Ingresos por semana</h2>
        <div class="agency-chart"><canvas id="agency-revenue" role="img" aria-label="Ingresos semanales en soles: 3 900, 5 200, 4 100 y 5 250."></canvas></div>
    </section>
    <section class="cat-surface cat-padding"><h2>Últimas ventas</h2><div class="table-wrap"><table class="cat-table">
        <thead><tr><th>Fecha</th><th>Cliente</th><th>Proyecto o producto</th><th>Importe</th></tr></thead>
        <tbody>@foreach([['30 nov','Casa Nativa','Identidad visual','2 400'],['28 nov','Lima Botánica','Pack de ilustraciones','650'],['27 nov','Estudio Río','Tote bags serigrafiadas','480'],['26 nov','Café Duna','Diseño de empaques','1 200']] as [$date,$client,$product,$amount])
            <tr><td>{{ $date }}</td><td>{{ $client }}</td><td>{{ $product }}</td><td>S/ {{ $amount }}</td></tr>
        @endforeach</tbody>
    </table></div></section>
</section>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/category-modules.css') }}">
@endpush
@push('scripts')
<script defer src="{{ asset('js/vendor/chart.umd.min.js') }}"></script>
<script defer src="{{ asset('js/agency-dashboard.js') }}"></script>
@endpush
