@extends('layouts.app')
@section('content')
<h1>Mi perfil</h1><p>{{ auth()->user()->nombre_completo }} · {{ auth()->user()->username }}</p>
<form method="POST" action="{{ route('profile.save') }}" class="card">@csrf<label>Moneda preferida<select name="moneda">@foreach($currencies as $currency)<option value="{{ $currency->simbolo }}" @selected(auth()->user()->moneda===$currency->simbolo)>{{ $currency->descripcion }}</option>@endforeach</select></label><button type="submit" class="btn btn-success">Guardar preferencia</button></form>
@can('admin_gestionar_roles')<form method="POST" action="{{ route('profile.logo') }}" enctype="multipart/form-data" class="card">@csrf<label>Logo PNG (hasta 1 MB)<input type="file" name="logo" accept="image/png" required></label><button type="submit" class="btn btn-success">Actualizar logo</button></form>@endcan
@endsection
