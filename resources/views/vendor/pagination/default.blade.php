@if ($paginator->hasPages())
    <ul class="pagination pagination-split footable-pagination m-t-10 m-b-0">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            {{--<li class="footable-page-arrow disabled"><a>&laquo;</a></li>--}}
        @else
            {{--<li class="footable-page-arrow"><a href="{{  }}"data-page="last" rel="last">»</a></li>--}}
            <li class="footable-page-arrow"><a href="{{ $paginator->url(1) }}" data-page="first" rel="first">&laquo;</a></li>
            <li class="footable-page-arrow"><a href="{{ $paginator->previousPageUrl() }}" data-page="prev" rel="prev">‹</a></li>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <li class="footable-page disabled"><a>{{ $element }}</a></li>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="footable-page active"><a data-page="0">{{ $page }}</a></li>
                    @else
                        <li class="footable-page"><a href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())

            <li class="footable-page-arrow"><a href="{{ $paginator->nextPageUrl() }}"data-page="next" rel="next">›</a></li>
            <li class="footable-page-arrow"><a href="{{ $paginator->url($paginator->lastPage()) }}"data-page="last" rel="last">»</a></li>

        @else
            <li class="footable-page-arrow disabled"><span>&raquo;</span></li>
        @endif
    </ul>

@endif
