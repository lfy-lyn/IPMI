@extends('layouts.internal')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Rekap Absensi</h2>
            <p class="text-gray-600">{{ $activity->name }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('activities.attendance.index', $activity) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Catat Absensi</a>
            <a href="{{ route('activities.show', $activity) }}" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded">Kembali</a>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach(['hadir' => ['Hadir', 'bg-green-50 text-green-800'], 'izin' => ['Izin', 'bg-blue-50 text-blue-800'], 'sakit' => ['Sakit', 'bg-yellow-50 text-yellow-800'], 'alpa' => ['Alpa', 'bg-red-50 text-red-800']] as $key => [$label, $cls])
            <div class="rounded-lg p-4 {{ $cls }}">
                <div class="text-2xl font-bold">{{ $summary[$key] }}</div>
                <div class="text-sm">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    @if($perMember->isEmpty())
        <div class="bg-white shadow rounded-lg p-6 text-gray-600">
            Belum ada absensi yang dicatat untuk kegiatan ini.
            <a class="text-blue-600 underline" href="{{ route('activities.attendance.index', $activity) }}">Catat absensi sekarang</a>.
        </div>
    @else
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Divisi</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hadir</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Izin</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Sakit</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Alpa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($perMember as $row)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row['member']->full_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $row['member']->division?->name }}</td>
                            <td class="px-4 py-3 text-center text-sm">{{ $row['hadir'] }}</td>
                            <td class="px-4 py-3 text-center text-sm">{{ $row['izin'] }}</td>
                            <td class="px-4 py-3 text-center text-sm">{{ $row['sakit'] }}</td>
                            <td class="px-4 py-3 text-center text-sm">{{ $row['alpa'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
