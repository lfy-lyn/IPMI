@extends('layouts.internal')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Kegiatan</h2>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('activities.update', $activity) }}" class="bg-white shadow rounded-lg p-6">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kegiatan *</label>
                <input type="text" name="name" value="{{ old('name', $activity->name) }}" required
                       class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                <textarea name="description" rows="4" 
                          class="w-full border border-gray-300 rounded px-3 py-2">{{ old('description', $activity->description) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai *</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $activity->start_date->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ old('end_date', $activity->end_date?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Cakupan Peserta</label>
                <select name="division_id" class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="">Seluruh IPMI</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division->id }}" {{ old('division_id', $activity->division_id) == $division->id ? 'selected' : '' }}>
                            {{ $division->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Mode Absensi *</label>
                <select name="attendance_mode" class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="per_event" {{ old('attendance_mode', $activity->attendance_mode) == 'per_event' ? 'selected' : '' }}>
                        Per Event
                    </option>
                    <option value="per_day" {{ old('attendance_mode', $activity->attendance_mode) == 'per_day' ? 'selected' : '' }}>
                        Per Hari
                    </option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Untuk "Per Hari", hari kegiatan dibuat otomatis dari tanggal mulai sampai tanggal selesai (maksimal 31 hari).</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                <select name="status" class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="upcoming" {{ old('status', $activity->status) == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    <option value="ongoing" {{ old('status', $activity->status) == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                    <option value="completed" {{ old('status', $activity->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ old('status', $activity->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('activities.show', $activity) }}" 
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
@endsection
