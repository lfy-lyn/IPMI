@extends('layouts.internal')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Preview Kenaikan Kelas</h1>
    <p class="text-sm text-gray-600 mb-6">
        Dari: <strong>{{ $sourceYear->name }}</strong> → Ke: <strong>{{ $targetYear->name }}</strong>
    </p>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($sourceClasses->isEmpty())
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            Tidak ada kelas pada tahun ajaran asal.
        </div>
        <a href="{{ route('class-promotion.index') }}" class="text-blue-600 hover:text-blue-900">
            ← Kembali
        </a>
    @elseif($targetClasses->isEmpty())
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            Tidak ada kelas aktif pada tahun ajaran tujuan. Buat kelas terlebih dahulu.
        </div>
        <div class="flex space-x-4">
            <a href="{{ route('class-promotion.index') }}" class="text-blue-600 hover:text-blue-900">
                ← Kembali
            </a>
            <a href="{{ route('classes.create') }}" class="text-blue-600 hover:text-blue-900">
                Tambah Kelas →
            </a>
        </div>
    @else
    <form action="{{ route('class-promotion.process') }}" method="POST" onsubmit="return confirm('Proses kenaikan kelas untuk semua pemetaan yang sudah diatur? Proses ini tidak dapat dibatalkan.')">
        @csrf
        <input type="hidden" name="source_year_id" value="{{ $sourceYear->id }}">
        <input type="hidden" name="target_year_id" value="{{ $targetYear->id }}">

        <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-lg font-medium text-gray-900">Pemetaan Kelas</h2>
                <p class="text-sm text-gray-600 mt-1">Tentukan kelas tujuan untuk setiap kelas asal</p>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas Asal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah Anggota</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas Tujuan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($sourceClasses as $sourceClass)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $sourceClass->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $sourceClass->member_histories_count }} anggota
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <select name="mappings[{{ $sourceClass->id }}]" 
                                    class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                                    aria-label="Kelas tujuan untuk {{ $sourceClass->name }}">
                                <option value="">{{ $sourceClass->member_histories_count > 0 ? '-- Tidak dinaikkan (mis. lulus) --' : '-- Tidak Ada Anggota --' }}</option>
                                @foreach($targetClasses as $targetClass)
                                    <option value="{{ $targetClass->id }}">
                                        {{ $targetClass->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-6">
            <p class="text-sm text-blue-800">
                <strong>Catatan:</strong>
            </p>
            <ul class="list-disc list-inside text-sm text-blue-800 mt-2 space-y-1">
                <li>Riwayat kelas lama akan dipertahankan</li>
                <li>Anggota yang tidak aktif akan dilewati</li>
                <li>Kelas yang tidak memiliki anggota tidak perlu dipetakan</li>
                <li>Proses akan berjalan dalam transaction (semua atau tidak sama sekali)</li>
            </ul>
        </div>

        <div class="flex justify-between">
            <a href="{{ route('class-promotion.index') }}" 
               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                ← Kembali
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Proses Kenaikan Kelas
            </button>
        </div>
    </form>
    @endif
</div>
@endsection
