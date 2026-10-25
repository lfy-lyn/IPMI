@extends('layouts.internal')
@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Absensi Sesi</h2>
            <p class="text-gray-600">{{ $program->name }} &middot; {{ $session->session_date->format('d M Y') }}{{ $session->agenda ? ' &middot; '.$session->agenda : '' }}</p>
        </div>
        <a href="{{ route('programs.show', $program) }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded">Kembali</a>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @if($members->isEmpty())
        <div class="bg-white shadow rounded-lg p-6 text-gray-600">Belum ada anggota aktif yang menjadi peserta program ini.</div>
    @else
        <form method="POST" action="{{ route('programs.sessions.record', [$program, $session]) }}" class="bg-white shadow rounded-lg p-4 sm:p-6">
            @csrf
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Divisi</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catatan</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                    @foreach($members as $i => $m)
                        @php $ex = $attendances->get($m->id); @endphp
                        <tr>
                            <td class="px-3 py-3">{{ $i + 1 }}</td>
                            <td class="px-3 py-3 whitespace-nowrap">{{ $m->full_name }}</td>
                            <td class="px-3 py-3 text-sm text-gray-600">{{ $m->division->name }}</td>
                            <td class="px-3 py-3">
                                <input type="hidden" name="attendances[{{ $i }}][member_id]" value="{{ $m->id }}">
                                <select name="attendances[{{ $i }}][status]" aria-label="Status {{ $m->full_name }}" class="border border-gray-300 rounded px-2 py-1 text-sm">
                                    @foreach(['hadir'=>'Hadir','izin'=>'Izin','sakit'=>'Sakit','alpa'=>'Alpa'] as $v => $l)
                                        <option value="{{ $v }}" @selected(($ex?->status ?? 'hadir') === $v)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-3"><input type="text" name="attendances[{{ $i }}][notes]" maxlength="500" value="{{ $ex?->notes }}" aria-label="Catatan {{ $m->full_name }}" class="border border-gray-300 rounded px-2 py-1 text-sm w-full min-w-[10rem]"></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6 flex justify-end"><button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">Simpan Absensi</button></div>
        </form>
    @endif
</div>
@endsection
