@extends('layouts.internal')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Tahun Ajaran</h1>
        <a href="{{ route('academic-years.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Tambah Tahun Ajaran
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Mulai</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Selesai</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($academicYears as $year)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $year->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $year->start_date->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $year->end_date->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($year->status === 'active')
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Aktif</span>
                        @elseif($year->status === 'upcoming')
                            <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">Mendatang</span>
                        @else
                            <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">Arsip</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('academic-years.edit', $year) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                        
                        @if($year->status !== 'active')
                        <form action="{{ route('academic-years.activate', $year) }}" method="POST" class="inline" 
                              onsubmit="return confirm('Aktifkan tahun ajaran ini? Tahun ajaran aktif lainnya akan diarsipkan.')">
                            @csrf
                            @method('POST')
                            <button type="submit" class="text-green-600 hover:text-green-900 mr-3">Aktifkan</button>
                        </form>
                        @endif

                        @if($year->status === 'active')
                        <form action="{{ route('academic-years.archive', $year) }}" method="POST" class="inline"
                              onsubmit="return confirm('Arsipkan tahun ajaran ini?')">
                            @csrf
                            @method('POST')
                            <button type="submit" class="text-orange-600 hover:text-orange-900">Arsipkan</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        Belum ada tahun ajaran.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $academicYears->links() }}
    </div>
</div>
@endsection
