@extends('layouts.guest')

@section('content')
<div class="max-w-md mx-auto">
    @if($error)
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6">
            <p class="font-semibold">Aktivasi Gagal</p>
            <p>{{ $error }}</p>
        </div>
        
        <div class="text-center">
            <a href="{{ route('home') }}" class="text-blue-600 hover:text-blue-800">
                Kembali ke Beranda
            </a>
        </div>
    @else
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Aktivasi Akun</h2>

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-sm text-blue-800">
                <strong>Selamat, {{ $fullName }}!</strong><br>
                Buat password untuk akun <strong>{{ $email }}</strong>
            </p>
        </div>

        <form method="POST" action="{{ route('activation.activate', ['token' => $token]) }}">
            @csrf

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    Password Baru <span class="text-red-500">*</span>
                </label>
                <input id="password" 
                       type="password" 
                       name="password" 
                       required
                       minlength="8"
                       class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Minimal 8 karakter</p>
            </div>

            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                    Konfirmasi Password <span class="text-red-500">*</span>
                </label>
                <input id="password_confirmation" 
                       type="password" 
                       name="password_confirmation" 
                       required
                       minlength="8"
                       class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded">
                Aktifkan Akun
            </button>
        </form>
    @endif
</div>
@endsection