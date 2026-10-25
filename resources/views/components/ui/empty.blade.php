{{--
    Empty state: tampil saat belum ada data (bukan data contoh).
    <x-ui.empty icon="calendar" title="Belum ada kegiatan">Penjelasan singkat.</x-ui.empty>
--}}
@props(['icon' => 'inbox', 'title'])
<div {{ $attributes->merge(['class' => 'empty']) }}>
    <span class="empty-icon"><x-ui.icon :name="$icon" /></span>
    <div class="empty-title">{{ $title }}</div>
    @if(trim((string) $slot) !== '')
        <p class="empty-text">{{ $slot }}</p>
    @endif
</div>
