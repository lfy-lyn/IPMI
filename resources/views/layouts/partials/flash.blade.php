{{--
    Notifikasi flash (success / error / warning).
    Banyak view lama sudah menampilkan flash sendiri; agar tidak tampil dobel,
    pesan yang sudah ada di dalam isi halaman ($content) tidak dirender lagi.
--}}
@php
    $flashes = [
        'success' => ['alert-success', 'check-circle'],
        'error' => ['alert-error', 'alert-circle'],
        'warning' => ['alert-warning', 'alert-triangle'],
    ];
@endphp
@foreach($flashes as $key => [$class, $icon])
    @if(session($key) && ! str_contains($content, e(session($key))))
        <div class="alert {{ $class }}" role="{{ $key === 'error' ? 'alert' : 'status' }}">
            <x-ui.icon :name="$icon" />
            <div>{{ session($key) }}</div>
        </div>
    @endif
@endforeach
