@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Navigasi halaman">
        <ul class="pager-list">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="pager-item" aria-disabled="true">Sebelumnya</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pager-item">Sebelumnya</a>
                @endif
            </li>
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pager-item">Berikutnya</a>
                @else
                    <span class="pager-item" aria-disabled="true">Berikutnya</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
