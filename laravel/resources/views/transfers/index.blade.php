@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/category-modules.css') }}">
@endpush
@section('content')
<section class="categories-page"><header class="cat-heading"><div><h1>Transferencias</h1><p>Una transferencia enviada descuenta el origen y permanece en tránsito hasta confirmar recepción o cancelación.</p></div></header>
<button type="button" class="btn btn-success" data-open-create="nueva-transferencia">Nueva transferencia</button><x-create-dialog id="nueva-transferencia" title="Nueva transferencia">
<form method="POST" action="{{ route('transfers.store') }}" data-item-url="{{ route('items.search') }}" data-products-only>@csrf<input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::uuid() }}">
<div class="form-grid">@foreach(['origen'=>'Almacén de origen','destino'=>'Almacén de destino'] as $name=>$label)<label>{{ $label }}<select name="{{ $name }}" required>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->nombre }}</option>@endforeach</select></label>@endforeach</div>
@include('components.item-lines',['prefix'=>'productos'])<button type="submit" class="btn btn-success">Crear transferencia pendiente</button></form>
</x-create-dialog>
<div class="cat-surface table-wrap"><table class="cat-table"><thead><tr><th>Número</th><th>Origen</th><th>Destino</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
@forelse($transfers as $transfer)<tr><td>{{ $transfer->id }}</td><td>{{ $transfer->origen_nombre }}</td><td>{{ $transfer->destino_nombre }}</td><td>{{ $transfer->estado }}</td><td>@if(in_array($transfer->estado,['pendiente','enviada']))<form method="POST" action="{{ route('transfers.transition',$transfer->id) }}" data-confirm="¿Confirmar el cambio de estado de la transferencia?">@csrf<label>Nuevo estado<select name="estado">@if($transfer->estado==='pendiente')<option value="enviada">Enviar</option>@else<option value="recibida">Confirmar recepción</option>@endif@unless(auth()->user()->isMultifunctional())<option value="cancelada">Cancelar</option>@endunless</select></label><button type="submit" class="btn btn-success">Confirmar</button></form>@endif
</td></tr>@empty<tr><td colspan="5">No hay transferencias.</td></tr>@endforelse</tbody></table></div>{{ $transfers->links('vendor.pagination.tailwind') }}
</section>
@endsection

