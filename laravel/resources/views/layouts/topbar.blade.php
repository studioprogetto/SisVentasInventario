<header class="topbar">
    <div class="topbar-context"><button type="button" id="nav-toggle" class="icon-button" aria-label="Menú" aria-expanded="false" aria-controls="main-nav"><x-icon name="menu"/></button><span class="breadcrumb">Espacio de trabajo <span>/</span> <strong data-page-title>Administración</strong></span></div>
    <div class="topbar-actions"><x-theme-toggle/><a class="user-profile" href="{{ route('profile.index') }}"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->nombre_completo,0,1)) }}</span><span class="user-meta"><strong>{{ auth()->user()->nombre_completo }}</strong><small>Mi perfil</small></span></a><form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-button logout-button" type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"><x-icon name="logout"/></button></form></div>
</header>

