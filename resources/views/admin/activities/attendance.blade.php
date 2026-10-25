@extends('layouts.internal')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Absensi Kegiatan</h2>
                <p class="text-gray-600">{{ $activity->name }}</p>
            </div>
            <a href="{{ route('activities.show', $activity) }}" 
               class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
                Kembali
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if($activity->attendance_mode === 'per_day')
            <form method="GET" class="bg-white shadow rounded-lg p-4 mb-4 flex flex-wrap items-end gap-3">
                <div>
                    <label for="day" class="block text-sm font-medium text-gray-700 mb-1">Hari kegiatan</label>
                    <select name="day" id="day" class="border border-gray-300 rounded px-2 py-1 text-sm" onchange="this.form.submit()">
                        @foreach($activity->activityDays->sortBy('date') as $day)
                            <option value="{{ $day->id }}" @selected($selectedDay && $selectedDay->id === $day->id)>
                                {{ $day->date->format('d M Y') }}{{ $day->description ? ' - '.$day->description : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <noscript><button class="bg-gray-200 px-3 py-1 rounded">Pilih</button></noscript>
            </form>
        @endif

        @if($activity->attendance_mode === 'per_day' && !$selectedDay)
            <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded">
                Kegiatan ini memakai absensi per hari, tetapi belum ada hari yang diatur.
                Tambahkan hari kegiatan terlebih dahulu pada halaman
                <a class="underline" href="{{ route('activities.edit', $activity) }}">ubah kegiatan</a>.
            </div>
        @elseif($members->isEmpty())
            <div class="bg-white shadow rounded-lg p-6 text-gray-600">
                Belum ada anggota aktif yang menjadi peserta kegiatan ini.
            </div>
        @else
            <form method="POST" action="{{ route('activities.attendance.record', $activity) }}" class="bg-white shadow rounded-lg p-4 sm:p-6">
                @csrf
                @if($selectedDay)
                    <input type="hidden" name="activity_day_id" value="{{ $selectedDay->id }}">
                @endif

                <p class="text-sm text-gray-600 mb-4">
                    Peserta: {{ $activity->division ? $activity->division->name : 'Seluruh Anggota IPMI' }}
                    ({{ $members->count() }} anggota)
                </p>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIS</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Divisi</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($members as $index => $member)
                                @php $existing = $attendances->get($member->id); @endphp
                                <tr>
                                    <td class="px-3 py-3">{{ $index + 1 }}</td>
                                    <td class="px-3 py-3 text-sm">{{ $member->nis }}</td>
                                    <td class="px-3 py-3 whitespace-nowrap">{{ $member->full_name }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-600">{{ $member->division->name }}</td>
                                    <td class="px-3 py-3">
                                        <input type="hidden" name="attendances[{{ $index }}][member_id]" value="{{ $member->id }}">
                                        <select name="attendances[{{ $index }}][status]" aria-label="Status {{ $member->full_name }}" class="border border-gray-300 rounded px-2 py-1 text-sm">
                                            @foreach(['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'] as $val => $label)
                                                <option value="{{ $val }}" @selected(($existing?->status ?? 'hadir') === $val)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="text" name="attendances[{{ $index }}][notes]" maxlength="500"
                                               value="{{ $existing?->notes }}" placeholder="Catatan (opsional)"
                                               aria-label="Catatan {{ $member->full_name }}"
                                               class="border border-gray-300 rounded px-2 py-1 text-sm w-full min-w-[10rem]">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <a href="{{ route('activities.attendance.summary', $activity) }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">Lihat Rekap</a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">Simpan Absensi</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
