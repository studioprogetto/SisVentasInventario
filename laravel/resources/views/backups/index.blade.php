@extends('layouts.app')
@section('content')
<h1>Copias de seguridad</h1><p>Estas copias son privadas y contienen datos sensibles. Guárdalas en un medio externo protegido y verifica su restauración en un entorno aislado.</p>
<form method="POST" action="{{ route('backups.create') }}">@csrf<button type="submit" class="btn btn-success">Generar copia consistente</button></form>
<div class="table-wrap"><table><thead><tr><th>Archivo</th><th>Tamaño</th><th>Acción</th></tr></thead><tbody>@foreach($files as $file)<tr><td>{{ $file['name'] }}</td><td>{{ number_format($file['bytes']/1024,1) }} KB</td><td><a href="{{ route('backups.download',$file['name']) }}">Descargar</a></td></tr>@endforeach</tbody></table></div>
@endsection
