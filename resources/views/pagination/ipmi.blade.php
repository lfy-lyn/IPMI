{{--
    Pagination untuk aplikasi internal. Dipakai sebagai default via
    AppServiceProvider (Paginator::defaultView). Tidak memakai varian dark:
    sehingga tampilan konsisten dengan tema terang dashboard.
--}}
@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Navigasi halaman">
        <p>
            Menampilkan <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
            dari <strong>{{ $paginator->total() }}</strong> data
        </p>

        <ul class="pager-list">
            {{-- Sebelumnya --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span class="pager-item" aria-disabled="true" aria-label="Halaman sebelumnya"><x-ui.icon name="chevron-left" class="icon-sm" /></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pager-item" aria-label="Halaman sebelumnya"><x-ui.icon name="chevron-left" class="icon-sm" /></a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="pager-item" aria-disabled="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="pager-item" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="pager-item" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Berikutnya --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pager-item" aria-label="Halaman berikutnya"><x-ui.icon name="chevron-right" class="icon-sm" /></a>
                @else
                    <span class="pager-item" aria-disabled="true" aria-label="Halaman berikutnya"><x-ui.icon name="chevron-right" class="icon-sm" /></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
