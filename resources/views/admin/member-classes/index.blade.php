@extends('layouts.internal')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Penetapan Kelas Anggota</h1>
        <p class="text-sm text-gray-600 mt-1">Tahun Ajaran: <strong>{{ $activeYear->name }}</strong></p>
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

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <!-- Filter Form -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <form action="{{ route('member-classes.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari Nama/NIS</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                       placeholder="Nama atau NIS"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
            </div>

            <div>
                <label for="division_id" class="block text-sm font-medium text-gray-700 mb-1">Divisi</label>
                <select name="division_id" id="division_id"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <option value="">Semua Divisi</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division->id }}" {{ request('division_id') == $division->id ? 'selected' : '' }}>
                            {{ $division->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="has_class" class="block text-sm font-medium text-gray-700 mb-1">Status Kelas</label>
                <select name="has_class" id="has_class"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <option value="">Semua</option>
                    <option value="yes" {{ request('has_class') === 'yes' ? 'selected' : '' }}>Sudah Ada Kelas</option>
                    <option value="no" {{ request('has_class') === 'no' ? 'selected' : '' }}>Belum Ada Kelas</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'division_id', 'has_class']))
                    <a href="{{ route('member-classes.index') }}" class="ml-2 px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Members Table -->
    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIS</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Divisi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas Saat Ini</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($members as $member)
                @php
                    $currentClass = $member->classHistories
                        ->filter(function($history) use ($activeYear) {
                            return $history->classRoom && $history->classRoom->academic_year_id === $activeYear->id;
                        })
                        ->first();
                @endphp
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $member->nis }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $member->full_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $member->division->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($currentClass)
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                                {{ $currentClass->classRoom->name }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button type="button" data-member-id="{{ $member->id }}" data-member-name="{{ $member->full_name }}" data-class-id="{{ $currentClass ? $currentClass->class_id : '' }}" onclick="openAssignModal(this)"
                                class="text-blue-600 hover:text-blue-900 mr-3">
                            {{ $currentClass ? 'Ubah' : 'Tetapkan' }}
                        </button>
                        
                        @if($currentClass)
                        <button type="button" data-member-id="{{ $member->id }}" data-member-name="{{ $member->full_name }}" onclick="openRemoveModal(this)" class="text-red-600 hover:text-red-900">Hapus</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        Tidak ada anggota ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $members->appends(request()->query())->links() }}
    </div>
</div>

<!-- Assign Class Modal -->
<div id="assignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Tetapkan Kelas</h3>
            <p class="text-sm text-gray-600 mb-4">Anggota: <strong id="memberName"></strong></p>
            
            <form id="assignForm" method="POST" action="">
                @csrf
                <div class="mb-4">
                    <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">Pilih Kelas</label>
                    <select name="class_id" id="class_id" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="">Pilih Kelas</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="reasonWrap" class="mb-4 hidden">
                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2 mb-2">
                        Anggota ini sudah punya kelas. Perpindahan rombel di tengah tahun ajaran tidak diperbolehkan; ubah hanya untuk <strong>koreksi data yang salah</strong>. Perubahan dicatat di audit log.
                    </p>
                    <label for="correction_reason" class="block text-sm font-medium text-gray-700 mb-1">Alasan koreksi</label>
                    <textarea name="correction_reason" id="correction_reason" rows="3" minlength="10" maxlength="500"
                              class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeAssignModal()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Class Modal -->
<div id="removeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Hapus Penetapan Kelas</h3>
        <p class="text-sm text-gray-600 mb-3">Anggota: <strong id="removeMemberName"></strong></p>
        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2 mb-3">
            Hanya untuk koreksi data yang salah. Tindakan ini dicatat di audit log.
        </p>
        <form id="removeForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <label for="remove_reason" class="block text-sm font-medium text-gray-700 mb-1">Alasan (wajib)</label>
            <textarea name="correction_reason" id="remove_reason" rows="3" required minlength="10" maxlength="500"
                      class="w-full border-gray-300 rounded-md shadow-sm mb-4"></textarea>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRemoveModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAssignModal(btn) {
    const classId = btn.dataset.classId || '';
    document.getElementById('memberName').textContent = btn.dataset.memberName;
    document.getElementById('assignForm').action = @json(url('/admin/penetapan-kelas')) + '/' + btn.dataset.memberId + '/tetapkan';
    document.getElementById('class_id').value = classId;
    document.getElementById('correction_reason').value = '';
    document.getElementById('reasonWrap').classList.toggle('hidden', classId === '');
    document.getElementById('assignModal').classList.remove('hidden');
}
function closeAssignModal() { document.getElementById('assignModal').classList.add('hidden'); }
function openRemoveModal(btn) {
    document.getElementById('removeMemberName').textContent = btn.dataset.memberName;
    document.getElementById('removeForm').action = @json(url('/admin/penetapan-kelas')) + '/' + btn.dataset.memberId + '/hapus';
    document.getElementById('remove_reason').value = '';
    document.getElementById('removeModal').classList.remove('hidden');
}
function closeRemoveModal() { document.getElementById('removeModal').classList.add('hidden'); }
</script>
@endsection
