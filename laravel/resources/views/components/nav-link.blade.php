@props(['href', 'active' => false, 'icon' => 'grid'])
<a aria-label="{{ trim((string) $slot) }}" title="{{ trim((string) $slot) }}" href="{{ $href }}" @class(['nav-link', 'is-active' => $active]) @if($active) aria-current="page" @endif><x-icon :name="$icon"/><span class="nav-label">{{ $slot }}</span>@if($active)<span class="active-dot"></span>@endif</a>
