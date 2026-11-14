@extends('layouts.app')
@section('title', 'Configuración de la empresa')
@section('content')
@php
$fields = [
    'name' => ['Nombre de la empresa', 'name', 'text', 120],
    'ruc' => ['RUC', 'ruc', 'text', 20],
    'address' => ['Dirección', 'address', 'text', 255],
    'district' => ['Distrito / ciudad', 'district', 'text', 150],
    'phone' => ['Teléfono', 'phone', 'text', 30],
    'email' => ['Email', 'email', 'email', 150],
    'website' => ['Sitio web', 'website', 'url', 255],
    'opening_weekdays' => ['Horario de lunes a viernes', 'opening_hours.weekdays', 'text', 80],
    'opening_weekend' => ['Horario de fin de semana', 'opening_hours.weekend', 'text', 80],
];
$logoUrl = route('brand.logo').'?v='.now()->timestamp;
@endphp
<div class="business-settings" id="business-settings">
    <div class="page-heading"><div><p class="eyebrow">ADMINISTRACIÓN · NEGOCIO</p><h1>Configuración de la empresa</h1><p>Datos comerciales, políticas de atención e identidad de tu negocio.</p></div><span class="bs-badge">Cambios mediante guardado</span></div>
    <div class="bs-grid">
        <div class="bs-column">
            <section class="bs-card">
                <div class="bs-heading"><span class="bs-symbol"><x-icon name="settings"/></span><div><h2>Datos generales</h2><p>Información fiscal y contacto comercial</p></div></div>
                <form method="POST" action="{{ route('settings.business.save') }}">
                    @csrf
                    <div class="bs-fields">
                        @foreach($fields as $name => [$label, $key, $type, $max])
                        <label for="bs-{{ $name }}" class="{{ in_array($name, ['address', 'website']) ? 'bs-wide' : '' }}">{{ $label }}
                            <input id="bs-{{ $name }}" name="{{ $name }}" type="{{ $type }}" maxlength="{{ $max }}" value="{{ old($name, config('business.'.$key)) }}" data-preview="{{ $name }}" data-saved="{{ config('business.'.$key) }}" @required($name === 'name') aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" @if($errors->has($name)) aria-describedby="bs-error-{{ $name }}" @endif>
                            @error($name)<small class="bs-error" id="bs-error-{{ $name }}">{{ $message }}</small>@enderror
                        </label>
                        @endforeach
                        <label class="bs-wide" for="bs-return_policy_days">Días de devolución
                            <span class="bs-stepper"><button type="button" class="btn-secondary" data-step="-1" aria-label="Disminuir días">−</button><input type="number" id="bs-return_policy_days" name="return_policy_days" min="1" max="365" step="1" value="{{ old('return_policy_days', config('business.return_policy_days')) }}" data-preview="return_policy_days" data-saved="{{ config('business.return_policy_days') }}"><button type="button" class="btn-secondary" data-step="1" aria-label="Aumentar días">+</button></span>
                            @error('return_policy_days')<small class="bs-error">{{ $message }}</small>@enderror
                            <small>Política utilizada por los comprobantes. Rango permitido: 1–365 días.</small>
                        </label>
                    </div>
                    <div class="bs-actions"><button type="button" class="btn-secondary" id="bs-reset">Restablecer valores guardados</button><button type="submit">Guardar configuración</button></div>
                </form>
            </section>
            <section class="bs-card">
                <div class="bs-heading"><span class="bs-symbol"><x-icon name="image"/></span><div><h2>Logo y marca</h2><p>Imagen utilizada por el sistema y los comprobantes</p></div></div>
                <form method="POST" action="{{ route('settings.business.logo') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="bs-brand">
                        <div class="bs-logo-box"><img src="{{ $logoUrl }}" data-logo-preview alt="Logo de la empresa"></div>
                        <div><label for="bs-logo" id="bs-dropzone" class="bs-dropzone"><x-icon name="image"/><strong>Arrastra tu logo o selecciona un archivo</strong><small>PNG, JPEG o WEBP · Máximo 2 MB</small><input type="file" id="bs-logo" name="logo" accept="image/png,image/jpeg,image/webp" required></label><p id="bs-logo-status" role="status" aria-live="polite">Logo guardado. No hay nueva selección.</p>@error('logo')<small class="bs-error">{{ $message }}</small>@enderror</div>
                    </div>
                    <div class="bs-actions"><button type="button" class="btn-secondary" id="bs-cancel-logo">Cancelar selección</button><button type="submit">Actualizar logo</button></div>
                </form>
            </section>
        </div>
        <aside class="bs-card bs-preview">
            <div class="bs-heading"><span class="bs-symbol"><x-icon name="image"/></span><div><h2>Vista previa en tiempo real</h2><p>Diseño orientativo, no es un comprobante emitido</p></div></div>
            <div class="bs-receipt">
                <img src="{{ $logoUrl }}" data-logo-preview alt="Logo en vista previa"><h3 data-ticket="name">{{ config('business.name') }}</h3><p>RUC: <span data-ticket="ruc">{{ config('business.ruc') }}</span></p>
                @foreach(['address', 'district', 'phone', 'email'] as $key)<p data-ticket="{{ $key }}">{{ config('business.'.$key) }}</p>@endforeach
                <div class="bs-receipt-rule"><strong>VISTA PREVIA · SIN VALOR FISCAL</strong><p>Usuario: {{ auth()->user()->name ?? auth()->user()->nombre ?? 'Usuario autenticado' }}</p><p>Los productos e importes se completan al registrar una venta.</p></div>
                <strong>HORARIOS DE ATENCIÓN</strong><p data-ticket="opening_weekdays">{{ config('business.opening_hours.weekdays') }}</p><p data-ticket="opening_weekend">{{ config('business.opening_hours.weekend') }}</p>
                <div class="bs-receipt-rule">Cambios y devoluciones dentro de <strong data-ticket="return_policy_days">{{ config('business.return_policy_days') }}</strong> días presentando el comprobante.</div><p data-ticket="website">{{ config('business.website') }}</p><p>¡Gracias por su preferencia!</p>
            </div>
            <div class="bs-identity"><span class="bs-badge">Vista previa local</span><strong data-ticket="name">{{ config('business.name') }}</strong><small>La vista previa no guarda cambios. Utiliza los botones de cada formulario.</small></div>
        </aside>
    </div>
</div>
@endsection
@push('styles')
<style>
.business-settings{display:grid;gap:1.5rem}.business-settings .page-heading{display:flex;justify-content:space-between;gap:1rem;align-items:center}.business-settings .page-heading p{color:var(--muted);font-size:.82rem}.business-settings .bs-grid{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:1.75rem;align-items:start}.business-settings .bs-column{display:grid;gap:1.75rem;min-width:0}.business-settings .bs-card{padding:1.6rem;border:1px solid var(--line);border-radius:26px;background:var(--surface);box-shadow:var(--shadow);animation:bs-enter .4s ease both}.business-settings .bs-heading{display:flex;gap:.85rem;align-items:center;padding-bottom:1.1rem;margin-bottom:1.3rem;border-bottom:1px solid var(--line)}.business-settings h2{font-size:1.05rem;margin:0}.business-settings .bs-heading p{font-size:.72rem;color:var(--muted);margin:.3rem 0 0}.business-settings .bs-symbol{display:grid;place-items:center;flex:0 0 42px;height:42px;border-radius:14px;box-shadow:var(--shadow);color:var(--accent)}.business-settings .bs-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.15rem}.business-settings label{display:grid;gap:.4rem;font-size:.75rem;font-weight:600;min-width:0}.business-settings input{width:100%;min-width:0;min-height:44px;padding:.7rem .85rem;border:1px solid var(--line);border-radius:12px;background:var(--bg);box-shadow:var(--inset);color:var(--text);transition:border-color .2s}.business-settings input:focus{border-color:var(--accent)}.business-settings input[aria-invalid=true]{border-color:var(--red)}.business-settings .bs-wide{grid-column:1/-1}.business-settings small{font-size:.68rem;font-weight:400;color:var(--muted)}.business-settings .bs-error{color:var(--red)}.business-settings .bs-stepper{display:flex;gap:.5rem;max-width:220px;align-items:center}.business-settings .bs-stepper input{text-align:center}.business-settings .bs-stepper button{padding:.5rem;min-width:44px}.business-settings .bs-actions{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:.7rem;margin-top:1.4rem;padding-top:1.2rem;border-top:1px solid var(--line)}.business-settings .bs-brand{display:grid;grid-template-columns:minmax(100px,.7fr) minmax(0,1.3fr);gap:1.3rem;align-items:center}.business-settings .bs-logo-box{display:grid;place-items:center;aspect-ratio:1;border-radius:22px;padding:1rem;background:var(--bg);box-shadow:var(--inset)}.business-settings .bs-logo-box img{max-width:100%;max-height:180px;object-fit:contain}.business-settings .bs-dropzone{padding:1.2rem;border:2px dashed var(--line);border-radius:18px;box-shadow:var(--inset);text-align:center;justify-items:center;cursor:pointer;transition:transform .2s,border-color .2s}.business-settings .bs-dropzone.is-dragging{border-color:var(--accent);background:var(--accent-soft);transform:scale(1.01)}.business-settings #bs-logo-status{font-size:.7rem;color:var(--muted);overflow-wrap:anywhere}.business-settings .bs-preview{position:sticky;top:1.2rem}.business-settings .bs-receipt{max-width:340px;margin:1rem auto;padding:1.6rem 1.25rem;background:var(--raised);color:var(--text);font:.73rem/1.6 monospace;text-align:center;overflow-wrap:anywhere;clip-path:polygon(0 1%,3% 0,6% 1%,9% 0,12% 1%,15% 0,100% 0,100% 99%,97% 100%,94% 99%,91% 100%,88% 99%,85% 100%,0 100%);box-shadow:var(--shadow)}.business-settings .bs-receipt img{width:64px;height:64px;object-fit:contain}.business-settings .bs-receipt h3{font-size:1rem;margin:.5rem 0}.business-settings .bs-receipt p{margin:.25rem 0}.business-settings .bs-receipt-rule{border-block:1px dashed var(--line);padding:1rem 0;margin:1rem 0}.business-settings .bs-identity{display:grid;gap:.5rem;padding:1rem;border-radius:16px;box-shadow:var(--inset);overflow-wrap:anywhere}.business-settings .bs-badge{display:inline-flex;width:fit-content;padding:.45rem .7rem;background:var(--accent-soft);color:var(--accent);border-radius:12px;font-size:.65rem;font-weight:700}
@keyframes bs-enter{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:1200px){.business-settings .bs-grid{grid-template-columns:1fr}.business-settings .bs-preview{position:static}}
@media(max-width:600px){.business-settings .bs-fields,.business-settings .bs-brand{grid-template-columns:1fr}.business-settings .bs-card{padding:1.1rem}.business-settings .page-heading{align-items:start;flex-direction:column}.business-settings .bs-actions button{width:100%}.business-settings .bs-logo-box{width:180px;justify-self:center}}
@media(prefers-reduced-motion:reduce){.business-settings .bs-card{animation:none}.business-settings .bs-dropzone,.business-settings input{transition:none}}
</style>
@endpush
@push('scripts')
<script>
(() => {
    const root = document.getElementById('business-settings');
    const fields = root.querySelectorAll('[data-preview]');
    function sync() {
        fields.forEach(input => root.querySelectorAll('[data-ticket="' + input.dataset.preview + '"]').forEach(target => { target.textContent = input.value; }));
    }
    fields.forEach(input => input.addEventListener('input', sync));
    root.querySelector('#bs-reset').addEventListener('click', () => { fields.forEach(input => { input.value = input.dataset.saved; }); sync(); });
    root.querySelectorAll('[data-step]').forEach(button => button.addEventListener('click', () => {
        const input = root.querySelector('#bs-return_policy_days');
        input.value = Math.max(1, Math.min(365, (Number(input.value) || 1) + Number(button.dataset.step)));
        sync();
    }));
    const fileInput = root.querySelector('#bs-logo');
    const dropzone = root.querySelector('#bs-dropzone');
    const status = root.querySelector('#bs-logo-status');
    const images = root.querySelectorAll('[data-logo-preview]');
    const savedLogo = images[0].src;
    let objectUrl = null;
    function cancelLogo() {
        fileInput.value = '';
        images.forEach(image => { image.src = savedLogo; });
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        status.textContent = 'Logo guardado. No hay nueva selección.';
    }
    function preview(file) {
        if (!file) return;
        if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
            cancelLogo();
            status.textContent = 'Archivo rechazado: utiliza PNG, JPEG o WEBP de máximo 2 MB.';
            return;
        }
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        images.forEach(image => { image.src = objectUrl; });
        status.textContent = 'Vista previa sin guardar: ' + file.name;
    }
    fileInput.addEventListener('change', () => preview(fileInput.files[0]));
    root.querySelector('#bs-cancel-logo').addEventListener('click', cancelLogo);
    dropzone.addEventListener('dragover', event => { event.preventDefault(); dropzone.classList.add('is-dragging'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('is-dragging'));
    dropzone.addEventListener('drop', event => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
        const file = event.dataTransfer.files[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        fileInput.files = transfer.files;
        preview(file);
    });
    window.addEventListener('pagehide', () => { if (objectUrl) URL.revokeObjectURL(objectUrl); });
    sync();
})();
</script>
@endpush
