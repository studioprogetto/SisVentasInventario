@props(['id', 'title'])
<dialog id="{{ $id }}" class="create-dialog" aria-labelledby="{{ $id }}-title" @if(old('_create_dialog') === $id && $errors->any()) data-reopen @endif>
    <header><h2 id="{{ $id }}-title">{{ $title }}</h2>
        <button type="button" class="btn btn-secondary" data-close-create>Cerrar</button>
    </header>
    {{ $slot }}
</dialog>
