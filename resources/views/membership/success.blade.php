@extends('layouts.guest')

@section('content')
<div class="text-center">
    <div class="mb-4">
        <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </div>

    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Pendaftaran Berhasil Dikirim</h2>
    
    <p class="text-gray-600 mb-6">
        Terima kasih telah mendaftar sebagai anggota IPMI.<br>
        Pengurus akan meninjau pendaftaran Anda dalam waktu dekat.
    </p>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-blue-800">
            <strong>Langkah Selanjutnya:</strong><br>
            Setelah pendaftaran Anda disetujui, kami akan mengirimkan link aktivasi akun ke email yang Anda daftarkan. 
            Silakan periksa inbox dan folder spam Anda.
        </p>
    </div>

    <div class="flex justify-center space-x-4">
        <a href="{{ route('home') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded">
            Kembali ke Beranda
        </a>
    </div>
</div>
@endsection