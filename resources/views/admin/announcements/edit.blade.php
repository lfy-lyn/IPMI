@extends('layouts.internal')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Pengumuman</h2>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('announcements.update', $announcement) }}" class="bg-white shadow rounded-lg p-6">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Judul *</label>
                <input type="text" name="title" value="{{ old('title', $announcement->title) }}" required
                       class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Isi Pengumuman *</label>
                <textarea name="content" rows="8" required
                          class="w-full border border-gray-300 rounded px-3 py-2">{{ old('content', $announcement->content) }}</textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Target *</label>
                <select name="target_type" id="target_type" required class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="all" {{ old('target_type', $announcement->target_type) == 'all' ? 'selected' : '' }}>Semua Anggota</option>
                    <option value="division" {{ old('target_type', $announcement->target_type) == 'division' ? 'selected' : '' }}>Divisi Tertentu</option>
                    <option value="pengurus" {{ old('target_type', $announcement->target_type) == 'pengurus' ? 'selected' : '' }}>Pengurus</option>
                </select>
            </div>

            <div class="mb-4" id="division_field" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Divisi</label>
                <select name="division_id" class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="">Pilih Divisi</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division->id }}" {{ old('division_id', $announcement->division_id) == $division->id ? 'selected' : '' }}>
                            {{ $division->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">Waktu Terbit (opsional)</label>
                <input type="datetime-local" name="published_at" id="published_at" value="{{ old('published_at', isset($announcement) && $announcement->published_at ? $announcement->published_at->format('Y-m-d\\TH:i') : '') }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">Hanya berlaku jika status "Published". Kosongkan untuk terbit sekarang; isi waktu mendatang untuk menjadwalkan.</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                <select name="status" class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="draft" {{ old('status', $announcement->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status', $announcement->status) == 'published' ? 'selected' : '' }}>Published</option>
                    <option value="archived" {{ old('status', $announcement->status) == 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('announcements.show', $announcement) }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded">
                    Batal
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('target_type').addEventListener('change', function() {
    const divisionField = document.getElementById('division_field');
    if (this.value === 'division') {
        divisionField.style.display = 'block';
    } else {
        divisionField.style.display = 'none';
    }
});

if (document.getElementById('target_type').value === 'division') {
    document.getElementById('division_field').style.display = 'block';
}
</script>
@endsection
