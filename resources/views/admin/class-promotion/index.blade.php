@extends('layouts.internal')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Kenaikan Kelas Massal</h1>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-6">
        <form action="{{ route('class-promotion.preview') }}" method="POST">
            @csrf

            <p class="text-sm text-gray-600 mb-6">
                Pilih tahun ajaran asal dan tujuan untuk memulai proses kenaikan kelas massal.
            </p>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="source_year_id" class="block text-sm font-medium text-gray-700 mb-1">Tahun Ajaran Asal</label>
                    <select name="source_year_id" id="source_year_id" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="">Pilih Tahun Ajaran</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ old('source_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->name }} ({{ ucfirst($year->status) }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Tahun ajaran tempat anggota saat ini berada</p>
                </div>

                <div>
                    <label for="target_year_id" class="block text-sm font-medium text-gray-700 mb-1">Tahun Ajaran Tujuan</label>
                    <select name="target_year_id" id="target_year_id" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="">Pilih Tahun Ajaran</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ old('target_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->name }} ({{ ucfirst($year->status) }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Tahun ajaran untuk kenaikan kelas</p>
                </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    <strong>Catatan:</strong> Proses ini akan membuat preview pemetaan kelas. Anda dapat meninjau dan menyesuaikan pemetaan sebelum menyimpan perubahan.
                </p>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Lanjutkan ke Preview
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
