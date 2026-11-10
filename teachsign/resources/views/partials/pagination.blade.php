@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="page-link is-disabled">‹ Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="page-link">‹ Sebelumnya</a>
        @endif

        @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            @if ($page == $paginator->currentPage())
                <span class="page-link is-current">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="page-link">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="page-link">Berikutnya ›</a>
        @else
            <span class="page-link is-disabled">Berikutnya ›</span>
        @endif
    </nav>
@endif
