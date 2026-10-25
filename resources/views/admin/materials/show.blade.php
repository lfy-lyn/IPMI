@extends('layouts.internal')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Detail Materi</h2>
            <div class="space-x-2">
                @if(Auth::user()->member && (Auth::user()->member->isKetuaUmum() || Auth::user()->member->isSekretaris() || (Auth::user()->member->isKetuaDivisi() && $material->division_id == Auth::user()->member->division_id)))
                    <a href="{{ route('materials.edit', $material) }}" 
                       class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded">
                        Edit
                    </a>
                @endif
                @if($material->hasFile())
                    <a href="{{ route('materials.download', $material) }}" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        Download
                    </a>
                @endif
                <a href="{{ route('materials.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
                    Kembali
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg p-6">
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Judul</h3>
                    <p class="mt-1 text-lg text-gray-900">{{ $material->title }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Divisi</h3>
                    <p class="mt-1 text-gray-900">{{ $material->division->name }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Status</h3>
                    <p class="mt-1">
                        <span class="px-2 py-1 text-xs rounded {{ $material->status == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($material->status) }}
                        </span>
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Dibuat Oleh</h3>
                    <p class="mt-1 text-gray-900">{{ $material->creator->member->full_name ?? 'N/A' }}</p>
                </div>
            </div>

            @if($material->description)
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-500">Deskripsi</h3>
                    <p class="mt-1 text-gray-900">{{ $material->description }}</p>
                </div>
            @endif

            <div class="border-t pt-4">
                @if($material->hasFile())
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-500">File</h3>
                        <p class="mt-1 text-blue-600">{{ basename($material->file_path) }}</p>
                    </div>
                @endif

                @if($material->hasUrl())
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Tautan Eksternal</h3>
                        <a href="{{ $material->external_url }}" target="_blank" 
                           class="mt-1 text-blue-600 hover:text-blue-800">
                            {{ $material->external_url }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
