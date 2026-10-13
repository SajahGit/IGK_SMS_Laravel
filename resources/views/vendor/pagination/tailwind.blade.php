@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="no-print flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-slate-500">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>

        <div class="flex flex-wrap gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-secondary btn-sm opacity-50">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary btn-sm">Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 py-1 text-slate-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn btn-primary btn-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn btn-secondary btn-sm">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary btn-sm">Next</a>
            @else
                <span class="btn btn-secondary btn-sm opacity-50">Next</span>
            @endif
        </div>
    </nav>
@endif
