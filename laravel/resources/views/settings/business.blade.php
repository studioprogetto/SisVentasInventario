@extends('layouts.app')

@section('title', 'Configuración de la empresa')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">ADMINISTRACIÓN · NEGOCIO</p>
        <h1>Configuración de la empresa</h1>
        <p>Actualiza los datos generales de tu negocio y aplícalos automáticamente en ventas, ticket y branding.</p>
    </div>
</div>

<div class="settings-grid">
    <section class="panel settings-card">
        <div class="pos-panel-title"><x-icon name="settings"/><h2>Datos generales</h2></div>
        <form method="POST" action="{{ route('settings.business.save') }}" class="stacked-form">
            @csrf
            <div class="form-grid two-columns">
                <label>Nombre de la empresa<input type="text" name="name" value="{{ old('name', config('business.name')) }}" required></label>
                <label>RUC<input type="text" name="ruc" value="{{ old('ruc', config('business.ruc')) }}"></label>
                <label class="full-width">Dirección<input type="text" name="address" value="{{ old('address', config('business.address')) }}"></label>
                <label>Distrito / ciudad<input type="text" name="district" value="{{ old('district', config('business.district')) }}"></label>
                <label>Teléfono<input type="text" name="phone" value="{{ old('phone', config('business.phone')) }}"></label>
                <label>Email<input type="email" name="email" value="{{ old('email', config('business.email')) }}"></label>
                <label class="full-width">Sitio web<input type="url" name="website" value="{{ old('website', config('business.website')) }}"></label>
                <label>Horario de lunes a viernes<input type="text" name="opening_weekdays" value="{{ old('opening_weekdays', config('business.opening_hours.weekdays')) }}"></label>
                <label>Horario de fin de semana<input type="text" name="opening_weekend" value="{{ old('opening_weekend', config('business.opening_hours.weekend')) }}"></label>
                <label>Días de devolución<input type="number" name="return_policy_days" min="1" max="365" value="{{ old('return_policy_days', config('business.return_policy_days')) }}"></label>
            </div>
            <div class="form-actions">
                <button type="submit">Guardar configuración</button>
            </div>
        </form>
    </section>

    <section class="panel settings-card">
        <div class="pos-panel-title"><x-icon name="image"/><h2>Logo y marca</h2></div>
        <div class="brand-preview">
            <img src="{{ route('brand.logo') }}?v={{ now()->timestamp }}" alt="Logo de la empresa" class="brand-preview__logo">
        </div>
        <form method="POST" action="{{ route('settings.business.logo') }}" enctype="multipart/form-data" class="stacked-form">
            @csrf
            <label>Logo de la empresa<input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required></label>
            <div class="form-actions">
                <button type="submit">Actualizar logo</button>
            </div>
        </form>
    </section>
</div>
@endsection
