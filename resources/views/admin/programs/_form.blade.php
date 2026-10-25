@php $isPusat = Auth::user()->member->isPengurusPusat(); @endphp
@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Program *</label>
        <input type="text" id="name" name="name" required maxlength="255" value="{{ old('name', $program->name ?? '') }}"
               placeholder="Contoh: Pembiasaan Pagi Membaca Al-Qur'an" class="w-full border border-gray-300 rounded-md px-3 py-2">
    </div>
    <div>
        <label for="schedule_note" class="block text-sm font-medium text-gray-700 mb-1">Jadwal (catatan)</label>
        <input type="text" id="schedule_note" name="schedule_note" maxlength="255" value="{{ old('schedule_note', $program->schedule_note ?? '') }}"
               placeholder="Contoh: Setiap Jumat pukul 06.30" class="w-full border border-gray-300 rounded-md px-3 py-2">
    </div>
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
        <textarea id="description" name="description" rows="3" maxlength="2000" class="w-full border border-gray-300 rounded-md px-3 py-2">{{ old('description', $program->description ?? '') }}</textarea>
    </div>
    <div>
        <label for="division_id" class="block text-sm font-medium text-gray-700 mb-1">Peserta</label>
        <select id="division_id" name="division_id" class="w-full border border-gray-300 rounded-md px-3 py-2">
            @if($isPusat)<option value="">Seluruh Anggota IPMI</option>@endif
            @foreach($divisions as $d)
                @if($isPusat || $d->id === Auth::user()->member->division_id)
                    <option value="{{ $d->id }}" @selected((string) old('division_id', $program->division_id ?? ($isPusat ? '' : Auth::user()->member->division_id)) === (string) $d->id)>Divisi {{ $d->name }}</option>
                @endif
            @endforeach
        </select>
    </div>
    <div>
        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
        <select id="status" name="status" class="w-full border border-gray-300 rounded-md px-3 py-2">
            <option value="active" @selected(old('status', $program->status ?? 'active') === 'active')>Aktif</option>
            <option value="inactive" @selected(old('status', $program->status ?? 'active') === 'inactive')>Nonaktif</option>
        </select>
    </div>
</div>
