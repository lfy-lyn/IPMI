@extends('layouts.internal')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Daftar Kegiatan</h2>
            @if(Auth::user()->member && (Auth::user()->member->isKetuaUmum() || Auth::user()->member->isSekretaris() || Auth::user()->member->isKetuaDivisi()))
                <a href="{{ route('activities.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    Tambah Kegiatan
                </a>
            @endif
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <form method="GET" action="{{ route('activities.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <input type="text" name="search" placeholder="Cari nama kegiatan..." 
                           value="{{ request('search') }}" 
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <select name="status" class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">Semua Status</option>
                        <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div>
                    <select name="division_id" class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">Semua Divisi</option>
                        <option value="all" {{ request('division_id') == 'all' ? 'selected' : '' }}>Seluruh IPMI</option>
                        @foreach($divisions as $division)
                            <option value="{{ $division->id }}" {{ request('division_id') == $division->id ? 'selected' : '' }}>
                                {{ $division->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cakupan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($activities as $activity)
                        <tr>
                            <td class="px-6 py-4">{{ $activity->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $activity->start_date->format('d/m/Y') }}
                                @if($activity->end_date)
                                    - {{ $activity->end_date->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                {{ $activity->division ? $activity->division->name : 'Seluruh IPMI' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded 
                                    {{ $activity->status == 'upcoming' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $activity->status == 'ongoing' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $activity->status == 'completed' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $activity->status == 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ ucfirst($activity->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <a href="{{ route('activities.show', $activity) }}" class="text-blue-600 hover:text-blue-800">Lihat</a>
                                <a href="{{ route('activities.attendance.index', $activity) }}" class="text-green-600 hover:text-green-800">Absensi</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                Tidak ada kegiatan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    </div>
</div>
@endsection
