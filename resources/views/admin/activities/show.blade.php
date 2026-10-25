@extends('layouts.internal')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Detail Kegiatan</h2>
            <div class="space-x-2">
                @if(Auth::user()->member && (Auth::user()->member->isKetuaUmum() || Auth::user()->member->isSekretaris() || (Auth::user()->member->isKetuaDivisi() && $activity->division_id == Auth::user()->member->division_id)))
                    <a href="{{ route('activities.edit', $activity) }}" 
                       class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded">
                        Edit
                    </a>
                @endif
                @if($canManageAttendance)
                    <a href="{{ route('activities.attendance.index', $activity) }}"
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        Kelola Absensi
                    </a>
                @endif
                <a href="{{ route('activities.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
                    Kembali
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Nama Kegiatan</h3>
                    <p class="mt-1 text-lg text-gray-900">{{ $activity->name }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Status</h3>
                    <p class="mt-1">
                        <span class="px-2 py-1 text-xs rounded 
                            {{ $activity->status == 'upcoming' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $activity->status == 'ongoing' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $activity->status == 'completed' ? 'bg-gray-100 text-gray-800' : '' }}
                            {{ $activity->status == 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($activity->status) }}
                        </span>
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Tanggal</h3>
                    <p class="mt-1 text-gray-900">
                        {{ $activity->start_date->format('d F Y') }}
                        @if($activity->end_date)
                            - {{ $activity->end_date->format('d F Y') }}
                        @endif
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Cakupan Peserta</h3>
                    <p class="mt-1 text-gray-900">
                        {{ $activity->division ? $activity->division->name : 'Seluruh IPMI' }}
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Mode Absensi</h3>
                    <p class="mt-1 text-gray-900">
                        {{ $activity->attendance_mode == 'per_event' ? 'Per Event' : 'Per Hari' }}
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Dibuat Oleh</h3>
                    <p class="mt-1 text-gray-900">
                        {{ $activity->creator->member->full_name ?? 'N/A' }}
                    </p>
                </div>
            </div>

            @if($activity->description)
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500">Deskripsi</h3>
                    <p class="mt-1 text-gray-900">{{ $activity->description }}</p>
                </div>
            @endif
        </div>

        @if($activity->activityDays->count() > 0)
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Hari Pelaksanaan</h3>
                <ul class="divide-y divide-gray-200">
                    @foreach($activity->activityDays as $day)
                        <li class="py-2">
                            <span class="font-medium">{{ $day->date->format('d F Y') }}</span>
                            @if($day->description)
                                <span class="text-gray-600">- {{ $day->description }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($canManageAttendance)
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Statistik Kehadiran</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach(['hadir' => ['Hadir', 'text-green-600'], 'izin' => ['Izin', 'text-blue-600'], 'sakit' => ['Sakit', 'text-yellow-600'], 'alpa' => ['Alpa', 'text-red-600']] as $key => [$label, $cls])
                    <div class="text-center">
                        <p class="text-3xl font-bold {{ $cls }}">{{ $attendanceCounts[$key] ?? 0 }}</p>
                        <p class="text-sm text-gray-600">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-right"><a class="text-blue-600 underline text-sm" href="{{ route('activities.attendance.summary', $activity) }}">Lihat rekap lengkap</a></p>
        </div>
        @endif
    </div>
</div>
@endsection
