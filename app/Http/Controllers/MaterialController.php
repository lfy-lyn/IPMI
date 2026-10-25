<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $member = Auth::user()->member;
        if (!$member) {
            abort(403, 'Anda harus terdaftar sebagai anggota.');
        }

        $query = Material::with(['division', 'creator.member']);

        // Filter based on authorization
        if ($member->isKetuaDivisi() && !$member->isKetuaUmum() && !$member->isSekretaris()) {
            $query->where('division_id', $member->division_id);
        } elseif (!$member->isKetuaUmum() && !$member->isSekretaris() && !$member->isKetuaDivisi()) {
            // Regular members only see active materials from their division
            $query->where('division_id', $member->division_id)
                  ->where('status', 'active');
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        $materials = $query->orderBy('created_at', 'desc')->paginate(15);
        $divisions = Division::where('status', 'active')->get();

        return view('admin.materials.index', compact('materials', 'divisions'));
    }

    public function create()
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris() && !$member->isKetuaDivisi())) {
            abort(403, 'Anda tidak memiliki akses untuk membuat materi.');
        }

        $divisions = Division::where('status', 'active')->get();
        return view('admin.materials.create', compact('divisions'));
    }

    public function store(Request $request)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris() && !$member->isKetuaDivisi())) {
            abort(403);
        }

        $validated = $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png|max:10240',
            'external_url' => 'nullable|url|max:500',
            'status' => 'required|in:active,archived',
        ]);

        // Ketua Divisi can only create materials for their division
        if ($member->isKetuaDivisi() && !$member->isKetuaUmum() && !$member->isSekretaris()) {
            if ($validated['division_id'] != $member->division_id) {
                abort(403, 'Anda hanya dapat membuat materi untuk divisi Anda.');
            }
        }

        // Must have either file or URL
        if (!$request->hasFile('file') && empty($validated['external_url'])) {
            return back()->withErrors(['file' => 'Materi harus memiliki file atau tautan eksternal.'])->withInput();
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('materials', 'private');
        }

        Material::create([
            'division_id' => $validated['division_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $filePath,
            'external_url' => $validated['external_url'] ?? null,
            'status' => $validated['status'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('materials.index')->with('success', 'Materi berhasil ditambahkan.');
    }

    public function show(Material $material)
    {
        $member = Auth::user()->member;
        if (!$material->canBeAccessedBy($member)) {
            abort(403, 'Anda tidak memiliki akses ke materi ini.');
        }

        $material->load(['division', 'creator.member']);
        return view('admin.materials.show', compact('material'));
    }

    public function edit(Material $material)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris() && !$member->isKetuaDivisi())) {
            abort(403);
        }

        if ($member->isKetuaDivisi() && !$member->isKetuaUmum() && !$member->isSekretaris()) {
            if ($material->division_id != $member->division_id) {
                abort(403, 'Anda hanya dapat mengedit materi divisi Anda.');
            }
        }

        $divisions = Division::where('status', 'active')->get();
        return view('admin.materials.edit', compact('material', 'divisions'));
    }

    public function update(Request $request, Material $material)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris() && !$member->isKetuaDivisi())) {
            abort(403);
        }

        if ($member->isKetuaDivisi() && !$member->isKetuaUmum() && !$member->isSekretaris()) {
            if ($material->division_id != $member->division_id) {
                abort(403);
            }
        }

        $validated = $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png|max:10240',
            'external_url' => 'nullable|url|max:500',
            'status' => 'required|in:active,archived',
        ]);

        // Ketua Divisi validation
        if ($member->isKetuaDivisi() && !$member->isKetuaUmum() && !$member->isSekretaris()) {
            if ($validated['division_id'] != $member->division_id) {
                abort(403);
            }
        }

        $filePath = $material->file_path;
        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($material->file_path) {
                Storage::disk('private')->delete($material->file_path);
            }
            $filePath = $request->file('file')->store('materials', 'private');
        }

        $material->update([
            'division_id' => $validated['division_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $filePath,
            'external_url' => $validated['external_url'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()->route('materials.show', $material)->with('success', 'Materi berhasil diperbarui.');
    }

    public function download(Material $material)
    {
        $member = Auth::user()->member;
        if (!$material->canBeAccessedBy($member)) {
            abort(403);
        }

        if (!$material->hasFile()) {
            abort(404, 'File tidak ditemukan.');
        }

        if (!Storage::disk('private')->exists($material->file_path)) {
            abort(404, 'File tidak ditemukan di penyimpanan.');
        }

        // Nama unduhan yang rapi: judul materi + ekstensi asli.
        $ext = pathinfo($material->file_path, PATHINFO_EXTENSION);
        $name = Str::slug($material->title) ?: 'materi';

        return Storage::disk('private')->download($material->file_path, $name . ($ext ? ".{$ext}" : ''));
    }

    public function archive(Material $material)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403, 'Hanya Ketua Umum dan Sekretaris yang dapat mengarsipkan materi.');
        }

        $material->update(['status' => 'archived']);

        return redirect()->route('materials.index')->with('success', 'Materi berhasil diarsipkan.');
    }
}
