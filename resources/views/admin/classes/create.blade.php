@extends('layouts.internal')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Tambah Kelas</h1>

    <div class="bg-white shadow rounded-lg p-6">
        <form action="{{ route('classes.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">Tahun Ajaran</label>
                <select name="academic_year_id" id="academic_year_id"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    <option value="">Pilih Tahun Ajaran</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
                @error('academic_year_id')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="grade_level" class="block text-sm font-medium text-gray-700 mb-1">Tingkat</label>
                    <input type="text" name="grade_level" id="grade_level" value="{{ old('grade_level') }}"
                           placeholder="X, XI, XII"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    @error('grade_level')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="major" class="block text-sm font-medium text-gray-700 mb-1">Jurusan</label>
                    <input type="text" name="major" id="major" value="{{ old('major') }}"
                           placeholder="RPL, TKJ"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    @error('major')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="class_number" class="block text-sm font-medium text-gray-700 mb-1">Nomor Rombel</label>
                    <input type="number" name="class_number" id="class_number" value="{{ old('class_number') }}"
                           placeholder="1, 2, 3"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required min="1">
                    @error('class_number')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-4 p-3 bg-gray-50 rounded">
                <p class="text-sm text-gray-600">
                    <strong>Preview Nama Kelas:</strong>
                    <span id="class_name_preview" class="text-gray-900 ml-2">-</span>
                </p>
            </div>

            <div class="mb-6">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
                @error('status')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('classes.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateClassNamePreview() {
    const gradeLevel = document.getElementById('grade_level').value.trim();
    const major = document.getElementById('major').value.trim();
    const classNumber = document.getElementById('class_number').value.trim();
    
    const preview = document.getElementById('class_name_preview');
    if (gradeLevel && major && classNumber) {
        preview.textContent = `${gradeLevel} ${major} ${classNumber}`;
    } else {
        preview.textContent = '-';
    }
}

document.getElementById('grade_level').addEventListener('input', updateClassNamePreview);
document.getElementById('major').addEventListener('input', updateClassNamePreview);
document.getElementById('class_number').addEventListener('input', updateClassNamePreview);
</script>
@endsection
