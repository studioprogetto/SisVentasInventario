@extends('layouts.app')
@section('title', 'Centro de inteligencia | '.config('business.name'))
@section('content')
@php
    $dates = request()->only(['desde', 'hasta']);
@endphp
<section class="report-center">
    <div class="rc-banner"><span class="eyebrow">Centro de inteligencia · {{ count($types) }} módulos</span><h1>Reportes consolidados</h1><p>Consulta operaciones documentadas y exporta en Excel, PDF o CSV.</p>
        <div class="rc-controls"><label>Buscar reporte<input id="report-search" type="search" placeholder="Nombre del reporte"></label><label>Área<select id="report-area"><option value="all">Todas</option><option value="comercial">Comercial</option><option value="inventario">Inventario</option><option value="finanzas">Finanzas y caja</option></select></label></div>
    </div>
    <div class="rc-grid">
    @foreach($types as $type => $definition)
        @can($definition[1])
        @php
            $area = in_array($type, ['inventario','movimientos','top-productos','stock-bajo']) ? 'inventario' : (in_array($type, ['ventas','compras','clientes','proveedores']) ? 'comercial' : 'finanzas');
            $icon = match($area) { 'inventario' => 'box', 'comercial' => 'cart', default => 'wallet' };
            $description = match($type) {
                'ventas' => 'Operaciones, clientes, moneda, importes y estado de cada venta.',
                'inventario' => 'Existencias actuales y valoración a precios actuales. No es un snapshot histórico.',
                'stock-bajo' => 'Existencias actuales por debajo o iguales al mínimo de reposición.',
                'rentabilidad' => 'Ganancia base según importe neto, cambio aplicado, costo documentado y devoluciones.',
                'clientes','proveedores','saldos' => 'Estado actual del registro; no representa un snapshot del periodo.',
                default => 'Información operativa documentada, filtros de fechas y exportaciones.'
            };
        @endphp
        <article class="rc-card" data-area="{{ $area }}" data-name="{{ $definition[0] }}"><div class="rc-card-head"><span class="rc-number">{{ $loop->iteration }}</span><x-icon :name="$icon" /></div><h2>{{ $definition[0] }}</h2><p>{{ $description }}</p><div class="rc-card-foot"><small>{{ ucfirst($area) }}</small><a class="btn btn-secondary" href="{{ route('reports.show', ['type' => $type] + $dates) }}">Ver reporte <x-icon name="arrow" /></a></div></article>
        @endcan
    @endforeach
    </div><p id="report-empty" hidden>No hay reportes que coincidan con la búsqueda.</p>
    @include('reports.operations')
</section>
@can('caja_gestionar')<a class="btn btn-success" href="{{ route('reports.my-cash') }}">Reporte de caja · Mis turnos y movimientos</a>@endcan
@endsection
@push('styles')
<style>
.report-center{display:grid;gap:24px}.report-center .rc-banner,.report-center .rc-card{background:var(--surface);border:1px solid var(--line);border-radius:24px;box-shadow:var(--shadow);padding:24px}.report-center h1{margin:8px 0;font-size:clamp(1.3rem,2vw,1.8rem)}.report-center p{color:var(--muted);font-size:.8rem}.report-center .rc-controls{display:flex;flex-wrap:wrap;gap:16px;margin-top:20px}.report-center label{display:grid;gap:6px;font-size:.72rem}.report-center input,.report-center select{background:var(--surface);color:var(--text);box-shadow:var(--inset);border:1px solid var(--line);border-radius:12px;padding:10px}.report-center .rc-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}.report-center .rc-card{display:flex;flex-direction:column;transition:transform .25s,box-shadow .25s}.report-center .rc-card:hover{transform:translateY(-3px)}.report-center .rc-card[hidden]{display:none}.report-center .rc-card-head,.report-center .rc-card-foot{display:flex;align-items:center;justify-content:space-between;gap:10px}.report-center .rc-card-head{color:var(--accent)}.report-center .rc-number{padding:8px 12px;border-radius:12px;box-shadow:var(--inset);font-weight:800}.report-center h2{font-size:1rem;margin-top:20px}.report-center .rc-card-foot{margin-top:auto;padding-top:18px;border-top:1px solid var(--line)}.report-center small{color:var(--muted);font-size:.65rem}.report-center .btn{padding:10px;font-size:.7rem}@media(max-width:1200px){.report-center .rc-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:900px){.report-center .rc-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:540px){.report-center .rc-grid{grid-template-columns:1fr}.report-center .rc-controls>*{width:100%}}
</style>
@endpush
@push('scripts')
<script defer src="{{ asset('js/report-search.js') }}"></script>
@endpush
