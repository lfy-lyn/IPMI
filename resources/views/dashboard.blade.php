@extends('layouts.internal')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Dashboard</h2>
        <p class="text-gray-600">Selamat datang di sistem IPMI.</p>
        
        @if(Auth::user()->member)
            <div class="mt-4">
                <h3 class="text-lg font-semibold text-gray-800">Profil Anggota</h3>
                <p class="text-gray-600">Nama: {{ Auth::user()->member->full_name }}</p>
                <p class="text-gray-600">NIS: {{ Auth::user()->member->nis }}</p>
                <p class="text-gray-600">Divisi: {{ Auth::user()->member->division->name }}</p>
            </div>
        @else
            <div class="mt-4">
                <p class="text-gray-600">Profil anggota belum tersedia.</p>
            </div>
        @endif
    </div>
</div>
@endsection