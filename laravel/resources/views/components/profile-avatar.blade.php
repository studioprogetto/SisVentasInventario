<span class="avatar">
    @if(auth()->user()->foto_perfil)
        <img src="{{ route('profile.photo', ['v' => basename(auth()->user()->foto_perfil)]) }}" alt="">
    @else
        {{ mb_strtoupper(mb_substr(auth()->user()->nombre_completo, 0, 1)) }}
    @endif
</span>
