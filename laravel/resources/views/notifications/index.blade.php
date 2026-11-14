@extends('layouts.app')
@section('title','Notificaciones')
@section('content')
<section class="activity-center">
<header><h1>{{ $trash ? 'Notificaciones eliminadas' : 'Notificaciones' }}</h1>
<a class="btn btn-secondary" href="{{ route('notifications.index', $trash ? ['conservar'=>1] : ['eliminadas'=>1]) }}">{{ $trash ? 'Volver a la bandeja' : 'Ver eliminadas' }}</a></header>
@if($trash)<p>Las notificaciones se eliminan definitivamente después de 30 días.</p>
@else<form method="POST" action="{{ route('notifications.preference') }}">@csrf
<label><input type="checkbox" name="auto" value="1" @checked(auth()->user()->avisos_autolectura)> Marcar como leídas al abrir la bandeja</label>
<button class="btn btn-secondary">Guardar preferencia</button></form>@endif
@foreach($requests as $approval)
@php($details = json_decode($approval->datos,true))
<article class="neo-card activity-card"><div><h2>Solicitud #{{ $approval->id }} · Venta #{{ $approval->venta }}</h2>
<p>Destino: {{ $details['destino'] ?? 'saldo' }} · {{ $details['observacion'] }}</p>
@foreach($details['productos'] as $line)<p>Línea #{{ $line['id_detalle_venta'] }} · {{ $line['cantidad'] }} unidades</p>@endforeach
<a href="{{ route('sales.ticket',$approval->venta) }}">Revisar ticket</a></div>
<form method="POST" action="{{ route('returns.decide',$approval->id) }}">@csrf
<button class="btn btn-success" name="decision" value="aprobada">Aprobar solicitud</button>
<button class="btn btn-danger" name="decision" value="rechazada">Rechazar</button></form></article>
@endforeach
@if($requests instanceof \Illuminate\Pagination\LengthAwarePaginator){{ $requests->links('vendor.pagination.tailwind') }}@endif
@forelse($items as $item)
<article class="neo-card activity-card"><div><small>{{ $item->created_at }} · {{ $item->leido ? 'Leída' : 'Sin leer' }}</small><h2>{{ $item->titulo }}</h2><p>{{ $item->mensaje }}</p></div>
<form method="POST" action="{{ route('notifications.update',$item->id) }}">@csrf @method('PATCH')
@if($trash)<button class="btn btn-secondary" name="accion" value="restaurar">Restaurar</button>
@else @if($item->url)<a class="btn btn-success" href="{{ $item->url }}">Ver detalle</a>@endif
<button class="btn btn-secondary" name="accion" value="{{ $item->leido ? 'pendiente' : 'leer' }}">{{ $item->leido ? 'Marcar sin leer' : 'Marcar leída' }}</button>
<button class="btn btn-danger" name="accion" value="eliminar">Eliminar</button>@endif</form></article>
@empty<p class="card">No hay notificaciones en esta bandeja.</p>@endforelse
{{ $items->links('vendor.pagination.tailwind') }}
</section>
@endsection
