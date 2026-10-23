@extends('layouts.internal')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.membership.index') }}" class="text-blue-600 hover:text-blue-800">
            ← Kembali ke Daftar
        </a>
    </div>

    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Detail Pendaftaran</h2>

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
                    <h3 class="text-lg font-semibold">{{ $application->full_name }}</h3>
                    <p class="text-sm text-gray-600">NIS: {{ $application->nis }}</p>
                </div>
                <div>
                    @if($application->status === 'pending')
                        <span class="px-3 py-1 text-sm bg-yellow-100 text-yellow-800 rounded">Pending</span>
                    @elseif($application->status === 'approved')
                        <span class="px-3 py-1 text-sm bg-green-100 text-green-800 rounded">Disetujui</span>
                    @else
                        <span class="px-3 py-1 text-sm bg-red-100 text-red-800 rounded">Ditolak</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-4 border-t">
                <div>
                    <p class="text-sm text-gray-600">Email</p>
                    <p class="font-medium">{{ $application->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Nomor Telepon</p>
                    <p class="font-medium">{{ $application->phone ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Divisi Pilihan</p>
                    <p class="font-medium">{{ $application->division->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Tanggal Daftar</p>
                    <p class="font-medium">{{ $application->created_at->format('d F Y, H:i') }}</p>
                </div>
            </div>

            @if($application->address)
                <div class="pt-4 border-t">
                    <p class="text-sm text-gray-600">Alamat</p>
                    <p class="font-medium">{{ $application->address }}</p>
                </div>
            @endif

            <div class="pt-4 border-t">
                <p class="text-sm text-gray-600 mb-2">Alasan Bergabung</p>
                <p class="text-gray-800">{{ $application->reason }}</p>
            </div>

            @if($application->status !== 'pending')
                <div class="pt-4 border-t">
                    <p class="text-sm text-gray-600">Ditinjau oleh</p>
                    <p class="font-medium">{{ $application->reviewer->email ?? '-' }}</p>
                    <p class="text-sm text-gray-500">{{ $application->reviewed_at->format('d F Y, H:i') }}</p>
                </div>

                @if($application->rejection_reason)
                    <div class="pt-4 border-t">
                        <p class="text-sm text-gray-600 mb-2">Alasan Penolakan</p>
                        <p class="text-red-700">{{ $application->rejection_reason }}</p>
                    </div>
                @endif
            @endif
        </div>

        @if($application->status === 'pending')
            <div class="px-6 py-4 bg-gray-50 border-t flex gap-4">
                <form method="POST" action="{{ route('admin.membership.approve', $application) }}" class="inline">
                    @csrf
                    <button type="submit" 
                            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded"
                            onclick="return confirm('Setujui pendaftaran ini?')">
                        Setujui
                    </button>
                </form>

                <button type="button" 
                        onclick="document.getElementById('reject-modal').classList.remove('hidden')"
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-6 rounded">
                    Tolak
                </button>
            </div>
        @endif
    </div>
</div>

<!-- Reject Modal -->
<div id="reject-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Tolak Pendaftaran</h3>
        
        <form method="POST" action="{{ route('admin.membership.reject', $application) }}">
            @csrf
            
            <div class="mb-4">
                <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Alasan Penolakan <span class="text-red-500">*</span>
                </label>
                <textarea id="rejection_reason" 
                          name="rejection_reason" 
                          rows="4" 
                          required
                          minlength="10"
                          maxlength="500"
                          class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          placeholder="Jelaskan alasan penolakan (minimal 10 karakter)"></textarea>
                <p class="text-xs text-gray-500 mt-1">Minimal 10 karakter</p>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                    Tolak
                </button>
                <button type="button" 
                        onclick="document.getElementById('reject-modal').classList.add('hidden')"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>
@endsection