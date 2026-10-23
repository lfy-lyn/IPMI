@extends('layouts.guest')

@section('content')
<div class="max-w-md mx-auto text-center">
    <div class="mb-4">
        <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </div>

    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Akun Berhasil Diaktifkan!</h2>
    
    <p class="text-gray-600 mb-6">
        Akun Anda telah aktif. Silakan login untuk mengakses sistem IPMI.
    </p>

    <a href="{{ route('login') }}" 
       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded">
        Login Sekarang
    </a>
</div>
@endsection