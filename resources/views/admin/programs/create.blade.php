@extends('layouts.internal')
@section('content')
<div class="max-w-2xl mx-auto py-6 px-4">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Tambah Program Rutin</h2>
    <form method="POST" action="{{ route('programs.store') }}" class="bg-white shadow rounded-lg p-6">
        @csrf
        @include('admin.programs._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('programs.index') }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700">Batal</a>
            <button class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">Simpan</button>
        </div>
    </form>
</div>
@endsection
