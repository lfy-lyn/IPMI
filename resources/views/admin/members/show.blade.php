@extends('layouts.internal')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.members.index') }}" class="text-blue-600 hover:text-blue-800">
            ← Kembali ke Daftar
        </a>
    </div>

    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Detail Anggota</h2>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6 space-y-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-semibold">{{ $member->full_name }}</h3>
                    <p class="text-sm text-gray-600">NIS: {{ $member->nis }}</p>
                </div>
                <div>
                    @if($member->status === 'active')
                        <span class="px-3 py-1 text-sm bg-green-100 text-green-800 rounded">Aktif</span>
                    @else
                        <span class="px-3 py-1 text-sm bg-gray-100 text-gray-800 rounded">Nonaktif</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-4 border-t">
                <div>
                    <p class="text-sm text-gray-600">Email</p>
                    <p class="font-medium">{{ $member->user->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Nomor Telepon</p>
                    <p class="font-medium">{{ $member->phone ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Divisi</p>
                    <p class="font-medium">{{ $member->division->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Status Akun</p>
                    <p class="font-medium">{{ ucfirst($member->user->status) }}</p>
                </div>
            </div>

            @if($member->positions->count() > 0)
                <div class="pt-4 border-t">
                    <p class="text-sm text-gray-600 mb-2">Jabatan Organisasi</p>
                    <div class="flex gap-2">
                        @foreach($member->positions as $position)
                            <span class="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded">{{ $position->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if(Auth::user()->member && (Auth::user()->member->isKetuaUmum() || Auth::user()->member->isSekretaris()))
            <div class="px-6 py-4 bg-gray-50 border-t flex gap-4">
                <a href="{{ route('admin.members.edit', $member) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded">
                    Edit Data
                </a>

                @if($member->status === 'active')
                    <form method="POST" action="{{ route('admin.members.deactivate', $member) }}" class="inline">
                        @csrf
                        <button type="submit" 
                                class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-6 rounded"
                                onclick="return confirm('Nonaktifkan anggota ini?')">
                            Nonaktifkan
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection