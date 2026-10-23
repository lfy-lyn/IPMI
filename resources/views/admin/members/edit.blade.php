@extends('layouts.internal')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.members.show', $member) }}" class="text-blue-600 hover:text-blue-800">
            ← Kembali
        </a>
    </div>

    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Edit Data Anggota</h2>

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <form method="POST" action="{{ route('admin.members.update', $member) }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="nis" class="block text-sm font-medium text-gray-700 mb-2">
                        NIS <span class="text-red-500">*</span>
                    </label>
                    <input id="nis" 
                           type="text" 
                           name="nis" 
                           value="{{ old('nis', $member->nis) }}" 
                           required
                           class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nis') border-red-500 @enderror">
                    @error('nis')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>
                    <input id="full_name" 
                           type="text" 
                           name="full_name" 
                           value="{{ old('full_name', $member->full_name) }}" 
                           required
                           class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('full_name') border-red-500 @enderror">
                    @error('full_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Nomor Telepon
                    </label>
                    <input id="phone" 
                           type="text" 
                           name="phone" 
                           value="{{ old('phone', $member->phone) }}"
                           class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="division_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Divisi <span class="text-red-500">*</span>
                    </label>
                    <select id="division_id" 
                            name="division_id" 
                            required
                            class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('division_id') border-red-500 @enderror">
                        @foreach($divisions as $division)
                            <option value="{{ $division->id }}" {{ old('division_id', $member->division_id) == $division->id ? 'selected' : '' }}>
                                {{ $division->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('division_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded">
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.members.show', $member) }}" 
                       class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 px-6 rounded text-center">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection