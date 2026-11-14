@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Paginación">
        <div class="pagination-summary">
            Mostrando <strong>{{ $paginator->firstItem() ?? 0 }}</strong> a <strong>{{ $paginator->lastItem() ?? 0 }}</strong> de <strong>{{ $paginator->total() }}</strong> resultados
        </div>
        <div class="pagination-links">
            @if ($paginator->onFirstPage())
                <span class="pagination-button is-disabled" aria-disabled="true">Anterior</span>
            @else
                <a class="pagination-button" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior">Anterior</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-number is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pagination-number" href="{{ $url }}" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pagination-button" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente">Siguiente</a>
            @else
                <span class="pagination-button is-disabled" aria-disabled="true">Siguiente</span>
            @endif
        </div>
    </nav>
@endif
