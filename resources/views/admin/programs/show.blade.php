@extends('layouts.internal')
@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap justify-between items-start gap-3 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $program->name }}</h2>
            <p class="text-sm text-gray-600">{{ $program->schedule_note }} &middot; {{ $program->division ? 'Divisi '.$program->division->name : 'Seluruh IPMI' }} &middot; {{ $program->status === 'active' ? 'Aktif' : 'Nonaktif' }}</p>
        </div>
        <div class="flex gap-2">
            @if($canManage)<a href="{{ route('programs.edit', $program) }}" class="px-4 py-2 bg-blue-600 text-white rounded">Ubah</a>@endif
            <a href="{{ route('programs.index') }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded">Kembali</a>
        </div>
    </div>

    @if($program->description)<div class="bg-white shadow rounded-lg p-4 mb-6 text-gray-700">{{ $program->description }}</div>@endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @if($canManage && $program->status === 'active')
        <form method="POST" action="{{ route('programs.sessions.store', $program) }}" class="bg-white shadow rounded-lg p-4 mb-6 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            @csrf
            <div><label for="session_date" class="block text-sm text-gray-700 mb-1">Tanggal sesi *</label>
                <input type="date" id="session_date" name="session_date" required value="{{ old('session_date', now()->toDateString()) }}" class="w-full border border-gray-300 rounded px-3 py-2"></div>
            <div><label for="start_time" class="block text-sm text-gray-700 mb-1">Jam mulai</label>
                <input type="time" id="start_time" name="start_time" value="{{ old('start_time') }}" class="w-full border border-gray-300 rounded px-3 py-2"></div>
            <div><label for="agenda" class="block text-sm text-gray-700 mb-1">Agenda</label>
                <input type="text" id="agenda" name="agenda" maxlength="500" value="{{ old('agenda') }}" class="w-full border border-gray-300 rounded px-3 py-2"></div>
            <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Buat Sesi &amp; Absen</button>
        </form>
    @endif

    <h3 class="text-lg font-semibold text-gray-900 mb-2">Daftar Sesi</h3>
    @if($sessions->isEmpty())
        <div class="bg-white shadow rounded-lg p-6 text-gray-600 mb-6">Belum ada sesi. @if($canManage)Buat sesi pertama dengan formulir di atas.@endif</div>
    @else
        <div class="bg-white shadow rounded-lg overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agenda</th>
                    @if($canManage)<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tercatat</th><th></th>@endif
                </tr></thead>
                <tbody class="divide-y divide-gray-200">
                @foreach($sessions as $s)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $s->session_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-sm">{{ $s->start_time ? substr($s->start_time, 0, 5) : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $s->agenda ?? '-' }}</td>
                        @if($canManage)
                            <td class="px-4 py-3 text-sm">{{ $s->attendances_count }} anggota</td>
                            <td class="px-4 py-3 text-right"><a class="text-blue-600 hover:underline" href="{{ route('programs.sessions.attendance', [$program, $s]) }}">Absensi</a></td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($canManage && $recap->isNotEmpty())
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Rekap Kehadiran per Anggota</h3>
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                    @foreach(['Hadir','Izin','Sakit','Alpa'] as $h)<th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ $h }}</th>@endforeach
                </tr></thead>
                <tbody class="divide-y divide-gray-200">
                @foreach($recap as $r)
                    <tr><td class="px-4 py-3 whitespace-nowrap text-sm">{{ $r['name'] }}</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $r['hadir'] }}</td><td class="px-4 py-3 text-center text-sm">{{ $r['izin'] }}</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $r['sakit'] }}</td><td class="px-4 py-3 text-center text-sm">{{ $r['alpa'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
