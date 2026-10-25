@extends('layouts.internal')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Pengumuman</h2>
            @if(Auth::user()->member && (Auth::user()->member->isKetuaUmum() || Auth::user()->member->isSekretaris()))
                <a href="{{ route('announcements.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    Buat Pengumuman
                </a>
            @endif
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <form method="GET" action="{{ route('announcements.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <input type="text" name="search" placeholder="Cari judul pengumuman..." 
                           value="{{ request('search') }}" 
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <select name="status" class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                        <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            @forelse($announcements as $announcement)
                <div class="bg-white shadow rounded-lg p-6 hover:shadow-lg transition">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                <a href="{{ route('announcements.show', $announcement) }}" class="hover:text-blue-600">
                                    {{ $announcement->title }}
                                </a>
                            </h3>
                            <p class="text-gray-600 mb-3">{{ Str::limit($announcement->content, 150) }}</p>
                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                <span>
                                    <span class="px-2 py-1 text-xs rounded 
                                        {{ $announcement->status == 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                        {{ $announcement->status == 'published' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $announcement->status == 'archived' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ ucfirst($announcement->status) }}
                                    </span>
                                </span>
                                <span>Target: 
                                    @if($announcement->target_type == 'all')
                                        Semua Anggota
                                    @elseif($announcement->target_type == 'division')
                                        {{ $announcement->division->name }}
                                    @else
                                        Pengurus
                                    @endif
                                </span>
                                <span>{{ $announcement->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <a href="{{ route('announcements.show', $announcement) }}" 
                               class="text-blue-600 hover:text-blue-800 text-sm">
                                Lihat Detail →
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow rounded-lg p-6 text-center text-gray-500">
                    Tidak ada pengumuman.
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $announcements->links() }}
        </div>
    </div>
</div>
@endsection
