<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $member = Auth::user()->member;

        if (!$member) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $search = trim((string) $request->get('search'));

        // Data pribadi (NIS, email) hanya untuk Ketua Umum/Sekretaris dan Ketua Divisi (divisinya).
        $showPrivate = $member->isPengurusPusat() || $member->isKetuaDivisi();

        $query = Member::with(['division', 'user', 'positions']);

        if (!$member->isPengurusPusat()) {
            if ($member->isKetuaDivisi()) {
                $query->where('division_id', $member->division_id);
            } else {
                // Anggota biasa: direktori terbatas, hanya anggota aktif.
                $query->where('status', 'active');
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search, $showPrivate) {
                $q->where('full_name', 'like', "%{$search}%");
                // Pencarian NIS hanya untuk yang boleh melihat NIS (mencegah penebakan NIS).
                if ($showPrivate) {
                    $q->orWhere('nis', 'like', "%{$search}%");
                }
            });
        }

        $members = $query->orderBy('full_name')->paginate(20)->withQueryString();

        return view('admin.members.index', compact('members', 'search', 'showPrivate'));
    }

    public function show(Member $member)
    {
        $currentMember = Auth::user()->member;

        if (!$currentMember) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        if (!$currentMember->canViewMember($member)) {
            abort(403, 'Anda tidak memiliki akses untuk melihat anggota ini.');
        }

        $showPrivate = $currentMember->canViewPrivateDataOf($member);
        $member->load(['division', 'user', 'positions']);
        $currentClass = $member->currentClass()?->classRoom;

        $assignablePositions = collect();
        if ($currentMember->isPengurusPusat()) {
            $assignablePositions = \App\Models\Position::orderBy('id')->get()
                ->filter(fn ($p) => $currentMember->isKetuaUmum() || in_array($p->name, ['Bendahara', 'Ketua Divisi'], true))
                ->reject(fn ($p) => $member->positions->contains('id', $p->id))
                ->values();
        }
        $canManagePositions = $currentMember->isPengurusPusat();

        return view('admin.members.show', compact('member', 'showPrivate', 'currentClass', 'assignablePositions', 'canManagePositions'));
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
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{6,20}$/'],
            'nis' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z\-]+$/', 'unique:members,nis,' . $member->id],
            'division_id' => ['required', 'exists:divisions,id'],
        ]);

        DB::transaction(function () use ($member, $validated) {
            $before = $member->only(['full_name', 'phone', 'nis', 'division_id']);
            $member->update($validated);

            AuditLog::record('member.updated', $member, "Data anggota {$member->full_name} diperbarui", [
                'sebelum' => $before,
                'sesudah' => $member->only(['full_name', 'phone', 'nis', 'division_id']),
            ]);
        });

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function deactivate(Member $member)
    {
        $currentMember = Auth::user()->member;

        if (!$currentMember) {
            abort(403);
        }

        // Siapa yang boleh menonaktifkan siapa (D-013).
        if ($currentMember->isPengurusPusat()) {
            // boleh, dengan batasan di bawah
        } elseif ($currentMember->isKetuaDivisi() && (int) $member->division_id === (int) $currentMember->division_id) {
            // Ketua Divisi hanya anggota divisinya
        } else {
            abort(403, 'Anda tidak memiliki akses untuk menonaktifkan anggota ini.');
        }

        if ($currentMember->is($member)) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        // Pengurus tingkat atas hanya dapat dinonaktifkan oleh Ketua Umum.
        if ($member->isKetuaUmum() && !$currentMember->isKetuaUmum()) {
            abort(403, 'Hanya Ketua Umum yang dapat menonaktifkan Ketua Umum.');
        }
        if ($member->isSekretaris() && !$currentMember->isPengurusPusat()) {
            abort(403, 'Ketua Divisi tidak dapat menonaktifkan Sekretaris.');
        }

        if ($member->status === 'inactive') {
            return back()->with('error', 'Anggota sudah dalam status nonaktif.');
        }

        DB::transaction(function () use ($member) {
            $member->update(['status' => 'inactive']);

            // Nonaktifkan juga akun login (data historis tidak dihapus, D-013/D-027).
            $member->user?->update(['status' => 'inactive']);

            AuditLog::record('member.deactivated', $member, "Anggota {$member->full_name} dinonaktifkan");
        });

        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Anggota berhasil dinonaktifkan.');
    }
}
