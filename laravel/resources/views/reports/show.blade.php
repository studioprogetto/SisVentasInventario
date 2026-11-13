@extends('layouts.app')
@section('content')
<h1>{{ $title }}</h1><form method="GET" class="filters"><label>Desde<input type="date" name="desde" value="{{ $since }}"></label><label>Hasta<input type="date" name="hasta" value="{{ $until }}"></label><button type="submit" class="btn btn-secondary">Consultar</button></form>
<p>Los importes sin moneda documentada se muestran por separado. La rentabilidad usa únicamente costos y tasas conservados en la venta; un valor vacío indica información histórica insuficiente.</p>
<div class="actions">@foreach(['csv'=>'CSV','xlsx'=>'Excel','pdf'=>'PDF'] as $format=>$label)<a class="btn btn-secondary" href="{{ route('reports.show',$type) }}?{{ http_build_query(['desde'=>$since,'hasta'=>$until,'formato'=>$format]) }}">Exportar {{ $label }}</a>@endforeach<button type="button" class="btn btn-ghost" data-print>Imprimir</button></div>
<div class="table-wrap"><table><caption>{{ $title }} · {{ $since }} al {{ $until }}</caption>@if($rows->isNotEmpty())<thead><tr>@foreach(array_keys((array)$rows->first()) as $key)<th>{{ ucfirst(str_replace('_',' ',$key)) }}</th>@endforeach</tr></thead>@endif<tbody>@forelse($rows as $row)<tr>@foreach((array)$row as $value)<td>{{ $value ?? 'No documentado' }}</td>@endforeach</tr>@empty<tr><td>No hay resultados para este periodo.</td></tr>@endforelse</tbody></table></div>{{ $rows->links() }}
@endsection
