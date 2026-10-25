<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $member = Auth::user()->member;
        if (!$member) {
            abort(403, 'Anda harus terdaftar sebagai anggota.');
        }

        $query = Announcement::with(['division', 'creator.member']);

        // Filter based on member access
        if (!$member->isKetuaUmum() && !$member->isSekretaris()) {
            // Draft, arsip, dan yang belum waktunya terbit tidak boleh terlihat.
            $query->published();

            $query->where(function($q) use ($member) {
                $q->where('target_type', 'all')
                  ->orWhere(function($q2) use ($member) {
                      $q2->where('target_type', 'division')
                         ->where('division_id', $member->division_id);
                  });
                
                if ($member->isKetuaDivisi()) {
                    $q->orWhere('target_type', 'pengurus');
                }
            });
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $announcements = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403, 'Anda tidak memiliki akses untuk membuat pengumuman.');
        }

        $divisions = Division::where('status', 'active')->get();
        return view('admin.announcements.create', compact('divisions'));
    }

    public function store(Request $request)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_type' => 'required|in:all,division,pengurus',
            'division_id' => 'required_if:target_type,division|nullable|exists:divisions,id',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
        ]);

        $publishedAt = null;
        if ($validated['status'] === 'published') {
            $publishedAt = !empty($validated['published_at']) ? \Illuminate\Support\Carbon::parse($validated['published_at']) : now();
        }

        Announcement::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'target_type' => $validated['target_type'],
            'division_id' => $validated['target_type'] === 'division' ? ($validated['division_id'] ?? null) : null,
            'status' => $validated['status'],
            'published_at' => $publishedAt,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('announcements.index')->with('success', 'Pengumuman berhasil dibuat.');
    }

    public function show(Announcement $announcement)
    {
        $member = Auth::user()->member;
        if (!$announcement->canBeAccessedBy($member)) {
            abort(403, 'Anda tidak memiliki akses ke pengumuman ini.');
        }

        $announcement->load(['division', 'creator.member']);
        return view('admin.announcements.show', compact('announcement'));
    }

    public function edit(Announcement $announcement)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403);
        }

        $divisions = Division::where('status', 'active')->get();
        return view('admin.announcements.edit', compact('announcement', 'divisions'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_type' => 'required|in:all,division,pengurus',
            'division_id' => 'required_if:target_type,division|nullable|exists:divisions,id',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
        ]);

        $publishedAt = $announcement->published_at;
        if ($validated['status'] === 'published') {
            if (!empty($validated['published_at'])) {
                $publishedAt = \Illuminate\Support\Carbon::parse($validated['published_at']);
            } elseif ($announcement->status !== 'published' || !$publishedAt) {
                $publishedAt = now();
            }
        }

        $announcement->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'target_type' => $validated['target_type'],
            'division_id' => $validated['target_type'] === 'division' ? ($validated['division_id'] ?? null) : null,
            'status' => $validated['status'],
            'published_at' => $publishedAt,
        ]);

        return redirect()->route('announcements.show', $announcement)->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function archive(Announcement $announcement)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403);
        }

        $announcement->update(['status' => 'archived']);

        return redirect()->route('announcements.index')->with('success', 'Pengumuman berhasil diarsipkan.');
    }
}
