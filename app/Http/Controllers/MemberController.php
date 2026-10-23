<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $member = $user->member;

        if (!$member) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola anggota.');
        }

        $search = $request->get('search');

        $query = Member::with(['division', 'user']);

        // Authorization: Ketua Divisi hanya lihat anggota divisinya
        if ($member->isKetuaDivisi() && !$member->isKetuaUmum() && !$member->isSekretaris()) {
            $query->where('division_id', $member->division_id);
        }

        // Search by name or NIS
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $members = $query->orderBy('full_name')->paginate(20);

        return view('admin.members.index', compact('members', 'search'));
    }

    public function show(Member $member)
    {
        $user = Auth::user();
        $currentMember = $user->member;

        if (!$currentMember) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        // Authorization check
        if (!$currentMember->canViewMember($member)) {
            abort(403, 'Anda tidak memiliki akses untuk melihat anggota ini.');
        }

        $member->load(['division', 'user', 'positions']);

        return view('admin.members.show', compact('member'));
    }

    public function edit(Member $member)
    {
        $user = Auth::user();
        $currentMember = $user->member;

        if (!$currentMember) {
            abort(403);
        }

        // Only Ketua Umum and Sekretaris can edit
        if (!$currentMember->isKetuaUmum() && !$currentMember->isSekretaris()) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah data anggota.');
        }

        $divisions = Division::where('status', 'active')->get();

        return view('admin.members.edit', compact('member', 'divisions'));
    }

    public function update(Request $request, Member $member)
    {
        $user = Auth::user();
        $currentMember = $user->member;

        if (!$currentMember) {
            abort(403);
        }

        // Only Ketua Umum and Sekretaris can edit
        if (!$currentMember->isKetuaUmum() && !$currentMember->isSekretaris()) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah data anggota.');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'nis' => ['required', 'string', 'max:20', 'unique:members,nis,' . $member->id],
            'division_id' => ['required', 'exists:divisions,id'],
        ]);

        DB::transaction(function () use ($member, $validated) {
            $member->update($validated);
        });

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function deactivate(Member $member)
    {
        $user = Auth::user();
        $currentMember = $user->member;

        if (!$currentMember) {
            abort(403);
        }

        // Authorization: check who can deactivate
        if ($currentMember->isKetuaUmum() || $currentMember->isSekretaris()) {
            // Can deactivate any member
        } elseif ($currentMember->isKetuaDivisi() && $member->division_id === $currentMember->division_id) {
            // Ketua Divisi can deactivate members in their division
        } else {
            abort(403, 'Anda tidak memiliki akses untuk menonaktifkan anggota ini.');
        }

        if ($member->status === 'inactive') {
            return back()->with('error', 'Anggota sudah dalam status nonaktif.');
        }

        DB::transaction(function () use ($member) {
            $member->update(['status' => 'inactive']);
            
            // Also deactivate user account
            $member->user->update(['status' => 'inactive']);
        });

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Anggota berhasil dinonaktifkan.');
    }
}