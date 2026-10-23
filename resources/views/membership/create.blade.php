@extends('layouts.guest')

@section('content')
<div>
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Pendaftaran Anggota IPMI</h2>

    <form method="POST" action="{{ route('membership.store') }}">
        @csrf

        <div class="mb-4">
            <label for="nis" class="block text-sm font-medium text-gray-700">NIS <span class="text-red-500">*</span></label>
            <input id="nis" type="text" name="nis" value="{{ old('nis') }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nis') border-red-500 @enderror">
            @error('nis')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="full_name" class="block text-sm font-medium text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
            <input id="full_name" type="text" name="full_name" value="{{ old('full_name') }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('full_name') border-red-500 @enderror">
            @error('full_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">Email Aktif <span class="text-red-500">*</span></label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('email') border-red-500 @enderror">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Email akan digunakan untuk aktivasi akun</p>
        </div>

        <div class="mb-4">
            <label for="phone" class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
            <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="address" class="block text-sm font-medium text-gray-700">Alamat</label>
            <textarea id="address" name="address" rows="2"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('address') }}</textarea>
            @error('address')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="division_id" class="block text-sm font-medium text-gray-700">Pilihan Divisi <span class="text-red-500">*</span></label>
            <select id="division_id" name="division_id" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('division_id') border-red-500 @enderror">
                <option value="">-- Pilih Divisi --</option>
                @foreach($divisions as $division)
                    <option value="{{ $division->id }}" {{ old('division_id') == $division->id ? 'selected' : '' }}>
                        {{ $division->name }}
                    </option>
                @endforeach
            </select>
            @error('division_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="reason" class="block text-sm font-medium text-gray-700">Alasan Bergabung <span class="text-red-500">*</span></label>
            <textarea id="reason" name="reason" rows="4" required minlength="20" maxlength="1000"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('reason') border-red-500 @enderror">{{ old('reason') }}</textarea>
            @error('reason')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Minimal 20 karakter</p>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-sm text-gray-600 hover:text-gray-900">
                Kembali ke Beranda
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded">
                Kirim Pendaftaran
            </button>
        </div>
    </form>
</div>
@endsection