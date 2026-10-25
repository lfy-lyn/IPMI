@extends('layouts.internal')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Detail Pengumuman</h2>
            <div class="space-x-2">
                @if(Auth::user()->member && (Auth::user()->member->isKetuaUmum() || Auth::user()->member->isSekretaris()))
                    <a href="{{ route('announcements.edit', $announcement) }}" 
                       class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded">
                        Edit
                    </a>
                @endif
                <a href="{{ route('announcements.index') }}" 
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
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $announcement->title }}</h1>
                    <span class="px-3 py-1 text-sm rounded 
                        {{ $announcement->status == 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                        {{ $announcement->status == 'published' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $announcement->status == 'archived' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ ucfirst($announcement->status) }}
                    </span>
                </div>
                
                <div class="flex items-center space-x-4 text-sm text-gray-500 mb-6">
                    <span>Dibuat: {{ $announcement->created_at->format('d F Y, H:i') }}</span>
                    @if($announcement->published_at)
                        <span>Dipublikasikan: {{ $announcement->published_at->format('d F Y, H:i') }}</span>
                    @endif
                    <span>Oleh: {{ $announcement->creator->member->full_name ?? 'N/A' }}</span>
                </div>

                <div class="border-t pt-4 mb-6">
                    <p class="text-gray-700 whitespace-pre-line">{{ $announcement->content }}</p>
                </div>

                <div class="border-t pt-4">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Informasi Target</h3>
                    <p class="text-gray-900">
                        @if($announcement->target_type == 'all')
                            <span class="font-medium">Semua Anggota IPMI</span>
                        @elseif($announcement->target_type == 'division')
                            <span class="font-medium">Divisi {{ $announcement->division->name }}</span>
                        @else
                            <span class="font-medium">Pengurus IPMI</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
