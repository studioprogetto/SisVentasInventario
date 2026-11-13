@extends('layouts.app')
@section('content')
<h1>Reportes</h1><div class="cards">@foreach($types as $type=>$definition)@can($definition[1])<a class="card btn btn-secondary" href="{{ route('reports.show',$type) }}" style="display:inline-flex;justify-content:center;width:max-content;">{{ $definition[0] }}</a>@endcan@endforeach</div>
@endsection
