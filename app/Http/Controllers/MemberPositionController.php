<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Penetapan jabatan organisasi (terpisah dari role sistem).
 * - Ketua Umum: semua jabatan.
 * - Sekretaris: hanya Bendahara dan Ketua Divisi.
 */
class MemberPositionController extends Controller
{
    private const SEKRETARIS_BOLEH = ['Bendahara', 'Ketua Divisi'];

    private function actor(): Member
    {
        $m = Auth::user()->member;
        if (!$m || !$m->isPengurusPusat()) {
            abort(403, 'Hanya Ketua Umum dan Sekretaris yang dapat mengelola jabatan.');
        }

        return $m;
    }

    private function authorizePosition(Member $actor, Position $position): void
    {
        if (!$actor->isKetuaUmum() && !in_array($position->name, self::SEKRETARIS_BOLEH, true)) {
            abort(403, "Jabatan {$position->name} hanya dapat dikelola oleh Ketua Umum.");
        }
    }

    public function assign(Request $request, Member $member)
    {
        $actor = $this->actor();
        $validated = $request->validate(['position_id' => 'required|integer|exists:positions,id']);
        $position = Position::findOrFail($validated['position_id']);
        $this->authorizePosition($actor, $position);

        if ($member->status !== 'active' || $member->user?->status === 'inactive') {
            return back()->with('error', 'Jabatan hanya dapat diberikan kepada anggota aktif.');
        }

        if ($member->positions()->whereKey($position->id)->exists()) {
            return back()->with('error', "Anggota ini sudah menjabat {$position->name}.");
        }

        $holders = Member::where('status', 'active')->whereHas('positions', fn ($q) => $q->whereKey($position->id));

        if ($position->name === 'Sekretaris' && $holders->exists()) {
            return back()->with('error', 'Sekretaris aktif sudah ada. Cabut jabatannya terlebih dahulu.');
        }

        if ($position->name === 'Ketua Divisi' && (clone $holders)->where('division_id', $member->division_id)->exists()) {
            return back()->with('error', 'Divisi ini sudah memiliki Ketua Divisi aktif. Cabut jabatannya terlebih dahulu.');
        }

        DB::transaction(function () use ($member, $position) {
            $member->positions()->attach($position->id);
            AuditLog::record('member.position_assigned', $member, "{$member->full_name} ditetapkan sebagai {$position->name}", [
                'position_id' => $position->id,
            ]);
        });

        return back()->with('success', "Jabatan {$position->name} berhasil ditetapkan.");
    }

    public function remove(Member $member, Position $position)
    {
        $actor = $this->actor();
        $this->authorizePosition($actor, $position);

        if (!$member->positions()->whereKey($position->id)->exists()) {
            return back()->with('error', 'Anggota tidak memegang jabatan tersebut.');
        }

        if ($position->name === 'Ketua Umum') {
            $others = Member::where('status', 'active')->whereKeyNot($member->id)
                ->whereHas('positions', fn ($q) => $q->whereKey($position->id))->exists();
            if (!$others) {
                return back()->with('error', 'Tidak dapat mencabut Ketua Umum terakhir. Tetapkan Ketua Umum pengganti terlebih dahulu.');
            }
        }

        DB::transaction(function () use ($member, $position) {
            $member->positions()->detach($position->id);
            AuditLog::record('member.position_removed', $member, "Jabatan {$position->name} dicabut dari {$member->full_name}", [
                'position_id' => $position->id,
            ]);
        });

        return back()->with('success', "Jabatan {$position->name} berhasil dicabut.");
    }
}
