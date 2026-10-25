@extends('layouts.internal')
@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Program Rutin</h2>
            <p class="text-sm text-gray-600">Kegiatan berulang seperti pembiasaan pagi, ekskul, dan latihan.</p>
        </div>
        @if(Auth::user()->member->isPengurusPusat() || Auth::user()->member->isKetuaDivisi())
            <a href="{{ route('programs.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Tambah Program</a>
        @endif
    </div>

    @if($programs->isEmpty())
        <div class="bg-white shadow rounded-lg p-6 text-gray-600">
            Belum ada program rutin.
            @if(Auth::user()->member->isPengurusPusat() || Auth::user()->member->isKetuaDivisi())
                Klik <strong>Tambah Program</strong> untuk membuat yang pertama.
            @endif
        </div>
    @else
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jadwal</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Peserta</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sesi</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200">
                @foreach($programs as $program)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $program->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $program->schedule_note ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $program->division ? 'Divisi '.$program->division->name : 'Seluruh IPMI' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $program->sessions_count }}</td>
                        <td class="px-4 py-3 text-sm">{{ $program->status === 'active' ? 'Aktif' : 'Nonaktif' }}</td>
                        <td class="px-4 py-3 text-right"><a class="text-blue-600 hover:underline" href="{{ route('programs.show', $program) }}">Lihat</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $programs->links() }}</div>
    @endif
</div>
@endsection
