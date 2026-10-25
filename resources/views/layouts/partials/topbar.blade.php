<header class="topbar">
    <button type="button" class="icon-btn menu-btn" data-nav-open aria-label="Buka menu" aria-expanded="false" aria-controls="sidebar">
        <x-ui.icon name="menu" />
    </button>

    <a href="{{ route('dashboard') }}" class="topbar-brand" aria-label="IPMI — ke Dashboard">
        <span class="brand-mark"><x-ui.icon name="moon" /></span>
        IPMI
    </a>

    <nav aria-label="Posisi halaman">
        <ol class="breadcrumb">
            @if($activeItem && $activeItem['pattern'] !== 'dashboard')
                <li><a href="{{ route('dashboard') }}">Dashboard</a><x-ui.icon name="chevron-right" class="icon-sm" /></li>
                @if($activeItem['group'])
                    <li><span>{{ $activeItem['group'] }}</span><x-ui.icon name="chevron-right" class="icon-sm" /></li>
                @endif
                <li><span aria-current="page">{{ $activeItem['label'] }}</span></li>
            @else
                <li><span aria-current="page">Dashboard</span></li>
            @endif
        </ol>
    </nav>

    <div class="account">
        <button type="button" class="account-btn" data-account-toggle aria-haspopup="true" aria-expanded="false" aria-controls="account-menu">
            <span class="avatar" aria-hidden="true">{{ $initials }}</span>
            <span class="account-meta">
                <span class="account-name">{{ $displayName }}</span>
                <span class="account-role">{{ $roleLabel }}</span>
            </span>
            <x-ui.icon name="chevron-down" class="icon-sm" />
            <span class="sr-only">Menu akun</span>
        </button>

        <div id="account-menu" class="dropdown-panel" data-account-panel hidden>
            <div class="dropdown-head">
                <span class="avatar avatar-lg" aria-hidden="true">{{ $initials }}</span>
                <div>
                    <div class="dropdown-name">{{ $displayName }}</div>
                    <div class="dropdown-sub">{{ Auth::user()->email }}</div>
                    <div class="dropdown-sub">{{ $roleLabel }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item is-danger">
                    <x-ui.icon name="log-out" />
                    Keluar
                </button>
            </form>
        </div>
    </div>
</header>
