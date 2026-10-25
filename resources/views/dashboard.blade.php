@extends('layouts.internal')

@section('content')
@if(! $member)
    <div class="page-head">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-sub">Selamat datang di sistem IPMI.</p>
        </div>
    </div>

    <section class="card">
        <x-ui.empty icon="user" title="Profil anggota belum tersedia">
            Akun Anda belum tertaut dengan data anggota, sehingga ringkasan organisasi belum dapat ditampilkan.
            Silakan hubungi Ketua Umum atau Sekretaris.
        </x-ui.empty>
    </section>
@else
    @php
        $firstName = \Illuminate\Support\Str::of($member->full_name)->trim()->before(' ')->toString();
        $todayLabel = now()->locale('id')->translatedFormat('l, j F Y');
        $roleLabel = $member->positions->pluck('name')->join(', ') ?: 'Anggota';
        $canCreateActivity = $isPusat || $member->isKetuaDivisi();
        $targetLabel = fn ($a) => match ($a->target_type) {
            'all' => 'Semua anggota',
            'division' => 'Divisi '.($a->division?->name ?? '-'),
            'pengurus' => 'Pengurus',
            default => '-',
        };
    @endphp

    {{-- 1. Sapaan --}}
    <div class="page-head">
        <div>
            <h1 class="page-title">Assalamu’alaikum, {{ $firstName }}</h1>
            <p class="page-sub">
                {{ $todayLabel }} · {{ $roleLabel }}@if($member->division) · Divisi {{ $member->division->name }}@endif
            </p>
        </div>
        @if($canCreateActivity)
            <a href="{{ route('activities.create') }}" class="btn btn-primary">
                <x-ui.icon name="plus" class="icon-sm" /> Tambah Kegiatan
            </a>
        @endif
    </div>

    {{-- 2–3. Ringkasan --}}
    <section class="stat-grid" aria-label="Ringkasan organisasi">
        @foreach($stats as $stat)
            <a href="{{ $stat['url'] }}" class="stat-card">
                <div class="stat-top">
                    <span class="stat-icon {{ $stat['tone'] === 'amber' ? 'is-amber' : '' }}">
                        <x-ui.icon :name="$stat['icon']" />
                    </span>
                    <x-ui.icon name="arrow-right" class="icon-sm" style="color:#cbd5e1" />
                </div>
                <div>
                    <div class="stat-value">{{ number_format($stat['value'], 0, ',', '.') }}</div>
                    <div class="stat-label">{{ $stat['label'] }}</div>
                    <div class="stat-hint">{{ $stat['hint'] }}</div>
                </div>
            </a>
        @endforeach
    </section>

    <div class="dash-grid" style="margin-top:1.5rem">
        {{-- Kolom utama --}}
        <div class="stack">
            {{-- 4. Kegiatan mendatang --}}
            <section class="card" aria-labelledby="h-kegiatan">
                <div class="card-head">
                    <h2 class="card-title" id="h-kegiatan"><x-ui.icon name="calendar" /> Kegiatan Mendatang</h2>
                    <a href="{{ route('activities.index') }}" class="card-link">Lihat semua <x-ui.icon name="arrow-right" class="icon-sm" /></a>
                </div>
                <div class="card-body">
                    @forelse($activities as $activity)
                        @if($loop->first)<ul class="list">@endif
                        @php
                            $start = $activity->start_date->copy()->locale('id');
                            $end = $activity->end_date?->copy()->locale('id');
                            $range = ($end && ! $end->isSameDay($start))
                                ? $start->translatedFormat('j M').' – '.$end->translatedFormat('j M Y')
                                : $start->translatedFormat('j M Y');
                        @endphp
                        <li>
                            <a href="{{ route('activities.show', $activity) }}" class="list-row">
                                <span class="date-chip" aria-hidden="true">
                                    <span class="d">{{ $start->format('j') }}</span>
                                    <span class="m">{{ $start->translatedFormat('M') }}</span>
                                </span>
                                <span class="row-main">
                                    <span class="row-title" style="display:block">{{ $activity->name }}</span>
                                    <span class="row-sub" style="display:block">{{ $range }} · {{ $activity->division?->name ? 'Divisi '.$activity->division->name : 'Seluruh IPMI' }}</span>
                                </span>
                                @if($activity->status === 'ongoing')
                                    <span class="badge badge-teal">Berlangsung</span>
                                @else
                                    <span class="badge badge-slate">Akan datang</span>
                                @endif
                            </a>
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <x-ui.empty icon="calendar" title="Belum ada kegiatan mendatang">
                            Kegiatan yang akan datang atau sedang berjalan akan muncul di sini.
                        </x-ui.empty>
                    @endforelse
                </div>
            </section>

            {{-- Sesi program rutin --}}
            <section class="card" aria-labelledby="h-sesi">
                <div class="card-head">
                    <h2 class="card-title" id="h-sesi"><x-ui.icon name="repeat" /> Sesi Program Rutin</h2>
                    <a href="{{ route('programs.index') }}" class="card-link">Lihat program <x-ui.icon name="arrow-right" class="icon-sm" /></a>
                </div>
                <div class="card-body">
                    @forelse($sessions as $session)
                        @if($loop->first)<ul class="list">@endif
                        @php
                            $date = $session->session_date->copy()->locale('id');
                            $time = $session->start_time ? \Illuminate\Support\Carbon::parse($session->start_time)->format('H.i') : null;
                            $scope = $session->program->division?->name ? 'Divisi '.$session->program->division->name : 'Seluruh IPMI';
                        @endphp
                        <li>
                            <a href="{{ route('programs.show', $session->program) }}" class="list-row">
                                <span class="date-chip" aria-hidden="true">
                                    <span class="d">{{ $date->format('j') }}</span>
                                    <span class="m">{{ $date->translatedFormat('M') }}</span>
                                </span>
                                <span class="row-main">
                                    <span class="row-title" style="display:block">{{ $session->program->name }}</span>
                                    <span class="row-sub" style="display:block">
                                        {{ $date->translatedFormat('l') }}@if($time), {{ $time }}@endif · {{ $scope }}@if($session->agenda) · {{ $session->agenda }}@endif
                                    </span>
                                </span>
                            </a>
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <x-ui.empty icon="repeat" title="Belum ada sesi terjadwal">
                            Sesi program rutin yang dijadwalkan akan muncul di sini.
                        </x-ui.empty>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Kolom samping --}}
        <div class="stack">
            {{-- 5. Pendaftaran menunggu (hanya pengurus pusat) --}}
            @if($isPusat)
                <section class="card" aria-labelledby="h-daftar">
                    <div class="card-head">
                        <h2 class="card-title" id="h-daftar"><x-ui.icon name="user-plus" /> Pendaftaran Menunggu</h2>
                        @if($pendingCount > 0)
                            <span class="badge badge-amber">{{ $pendingCount }} perlu ditinjau</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @forelse($pendingApplications as $application)
                            @if($loop->first)<ul class="list">@endif
                            <li>
                                <a href="{{ route('admin.membership.show', $application) }}" class="list-row">
                                    <span class="avatar" aria-hidden="true">{{ \App\Support\InternalMenu::initials($application->full_name) }}</span>
                                    <span class="row-main">
                                        <span class="row-title" style="display:block">{{ $application->full_name }}</span>
                                        <span class="row-sub" style="display:block">
                                            {{ $application->division?->name ? 'Divisi '.$application->division->name : 'Divisi belum dipilih' }}
                                            · {{ $application->created_at->locale('id')->diffForHumans() }}
                                        </span>
                                    </span>
                                    <x-ui.icon name="chevron-right" class="icon-sm" style="color:#cbd5e1" />
                                </a>
                            </li>
                            @if($loop->last)</ul>@endif
                        @empty
                            <x-ui.empty icon="check-circle" title="Tidak ada antrean">
                                Semua pendaftaran calon anggota sudah ditinjau.
                            </x-ui.empty>
                        @endforelse

                        @if($pendingCount > $pendingApplications->count())
                            <a href="{{ route('admin.membership.index', ['status' => 'pending']) }}" class="card-link" style="margin-top:.75rem">
                                Lihat {{ $pendingCount - $pendingApplications->count() }} lainnya <x-ui.icon name="arrow-right" class="icon-sm" />
                            </a>
                        @endif
                    </div>
                </section>
            @endif

            {{-- 6. Pengumuman terbaru --}}
            <section class="card" aria-labelledby="h-info">
                <div class="card-head">
                    <h2 class="card-title" id="h-info"><x-ui.icon name="megaphone" /> Pengumuman Terbaru</h2>
                    <a href="{{ route('announcements.index') }}" class="card-link">Semua</a>
                </div>
                <div class="card-body">
                    @forelse($announcements as $announcement)
                        @if($loop->first)<ul class="list">@endif
                        <li>
                            <a href="{{ route('announcements.show', $announcement) }}" class="list-row">
                                <span class="row-main">
                                    <span class="row-title" style="display:block">{{ $announcement->title }}</span>
                                    <span class="row-sub" style="display:block">
                                        {{ $targetLabel($announcement) }} · {{ $announcement->published_at->locale('id')->diffForHumans() }}
                                    </span>
                                </span>
                            </a>
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <x-ui.empty icon="megaphone" title="Belum ada pengumuman">
                            Pengumuman yang diterbitkan untuk Anda akan muncul di sini.
                        </x-ui.empty>
                    @endforelse
                </div>
            </section>

            {{-- Pintasan sesuai izin --}}
            <section class="card" aria-labelledby="h-pintas">
                <div class="card-head">
                    <h2 class="card-title" id="h-pintas"><x-ui.icon name="grid" /> Pintasan</h2>
                </div>
                <div class="card-body">
                    <ul class="shortcut-list">
                        @foreach($shortcuts as $shortcut)
                            <li>
                                <a href="{{ $shortcut['url'] }}" class="shortcut">
                                    <x-ui.icon :name="$shortcut['icon']" />
                                    {{ $shortcut['label'] }}
                                    <x-ui.icon name="chevron-right" class="icon-sm arrow" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        </div>
    </div>
@endif
@endsection
