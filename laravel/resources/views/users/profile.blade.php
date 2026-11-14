@extends('layouts.app')
@section('content')
@php($user = auth()->user())
<section class="profile-page">
<header class="profile-heading"><x-profile-avatar /><div><h1>Gestionar mi perfil</h1><p>{{ $user->nombre_completo }} · {{ $user->username }}</p></div></header>
<form method="POST" action="{{ route('profile.details') }}" enctype="multipart/form-data" class="card profile-form">@csrf
<h2>Datos personales</h2>
<label>Nombres<input name="nombres" autocomplete="given-name" maxlength="100" required value="{{ old('nombres', $user->nombres ?? $user->nombre_completo) }}"></label>
<label>Apellidos<input name="apellidos" autocomplete="family-name" maxlength="100" required value="{{ old('apellidos', $user->apellidos) }}"></label>
<label>Correo electrónico<input type="email" name="correo" autocomplete="email" maxlength="254" value="{{ old('correo', $user->correo) }}"></label>
<label>Celular<input type="tel" name="celular" autocomplete="tel" maxlength="25" value="{{ old('celular', $user->celular) }}"></label>
<label>Fecha de nacimiento<input type="date" name="fecha_nacimiento" autocomplete="bday" max="{{ today()->toDateString() }}" value="{{ old('fecha_nacimiento', $user->fecha_nacimiento) }}"></label>
<label>Foto de perfil · JPG, PNG o WebP, hasta 2 MB<input type="file" name="foto" accept="image/jpeg,image/png,image/webp"></label>
<button class="btn btn-success">Guardar datos personales</button></form>
<form method="POST" action="{{ route('profile.password') }}" class="card profile-form">@csrf
<h2>Cambiar contraseña</h2><label>Contraseña actual<input type="password" name="current_password" autocomplete="current-password" required></label>
<label>Nueva contraseña<input type="password" name="password" autocomplete="new-password" minlength="8" maxlength="72" required></label>
<label>Confirmar contraseña<input type="password" name="password_confirmation" autocomplete="new-password" required></label>
<button class="btn btn-success">Actualizar contraseña</button></form>
<form method="POST" action="{{ route('profile.save') }}" class="card">@csrf<label>Moneda preferida<select name="moneda">@foreach($currencies as $currency)<option value="{{ $currency->simbolo }}" @selected(auth()->user()->moneda===$currency->simbolo)>{{ $currency->descripcion }}</option>@endforeach</select></label><button type="submit" class="btn btn-success">Guardar preferencia</button></form>
@can('admin_gestionar_roles')<form method="POST" action="{{ route('profile.logo') }}" enctype="multipart/form-data" class="card">@csrf<label>Logo PNG (hasta 1 MB)<input type="file" name="logo" accept="image/png" required></label><button type="submit" class="btn btn-success">Actualizar logo</button></form>@endcan
</section>
@endsection
