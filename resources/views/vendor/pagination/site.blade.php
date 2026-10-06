@if ($paginator->hasPages())
  <nav class="pager-site" role="navigation" aria-label="Pagination">
    @if ($paginator->onFirstPage())
      <span class="pager-site__btn is-disabled" aria-disabled="true">&lsaquo;</span>
    @else
      <a class="pager-site__btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo;</a>
    @endif

    @foreach ($elements as $element)
      @if (is_string($element))
        <span class="pager-site__dots" aria-disabled="true">{{ $element }}</span>
      @endif

      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <span class="pager-site__btn is-current" aria-current="page">{{ $page }}</span>
          @else
            <a class="pager-site__btn" href="{{ $url }}">{{ $page }}</a>
          @endif
        @endforeach
      @endif
    @endforeach

    @if ($paginator->hasMorePages())
      <a class="pager-site__btn" href="{{ $paginator->nextPageUrl() }}" rel="next">&rsaquo;</a>
    @else
      <span class="pager-site__btn is-disabled" aria-disabled="true">&rsaquo;</span>
    @endif
  </nav>
@endif
