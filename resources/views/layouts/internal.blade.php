<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $me = Auth::user()->member;
        $menuGroups = \App\Support\InternalMenu::groups($me);
        $activeItem = \App\Support\InternalMenu::active($menuGroups);

        $displayName = $me?->full_name ?: Auth::user()->email;
        $initials = \App\Support\InternalMenu::initials($displayName);
        $roleLabel = $me
            ? ($me->positions->pluck('name')->join(', ') ?: 'Anggota')
            : 'Akun';

        // Isi halaman dirender lebih dulu agar flash yang sudah ditampilkan view tidak dobel.
        $content = $__env->yieldContent('content');
    @endphp
    <title>{{ $activeItem['label'] ?? 'Dashboard' }} — {{ config('app.name', 'IPMI') }}</title>
    @fonts(['inter', 'plus-jakarta-sans'])
    @vite(['resources/css/app.css', 'resources/css/dashboard.css', 'resources/js/app.js', 'resources/js/dashboard.js'])
    @livewireStyles
</head>
<body class="ipmi-app">
    <a href="#konten" class="skip-link">Lewati ke konten utama</a>

    <div class="app-shell">
        <div class="sidebar-backdrop" data-nav-backdrop aria-hidden="true"></div>

        @include('layouts.partials.sidebar')

        <div class="app-main">
            @include('layouts.partials.topbar')

            <main id="konten" class="content" tabindex="-1">
                @include('layouts.partials.flash')

                {!! $content !!}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
