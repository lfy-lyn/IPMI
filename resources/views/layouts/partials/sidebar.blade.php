<aside id="sidebar" class="sidebar" aria-label="Navigasi utama">
    <div class="sidebar-head">
        <a href="{{ route('dashboard') }}" class="brand" aria-label="IPMI — ke Dashboard">
            <span class="brand-mark"><x-ui.icon name="moon" /></span>
            <span class="brand-text">
                <span class="brand-name">IPMI</span>
                <span class="brand-tag">Sistem Informasi</span>
            </span>
        </a>
        <button type="button" class="icon-btn sidebar-close" data-nav-close aria-label="Tutup menu">
            <x-ui.icon name="x" />
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Menu">
        @foreach($menuGroups as $group)
            <div>
                @if($group['label'])
                    <p class="nav-group-label">{{ $group['label'] }}</p>
                @endif
                <ul class="nav-list">
                    @foreach($group['items'] as $item)
                        @php $isActive = request()->is($item['pattern']); @endphp
                        <li>
                            <a href="{{ $item['url'] }}" class="nav-link" @if($isActive) aria-current="page" @endif>
                                <x-ui.icon :name="$item['icon']" />
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="sidebar-foot">
        Ikatan Pelajar Masjid Indonesia
    </div>
</aside>
