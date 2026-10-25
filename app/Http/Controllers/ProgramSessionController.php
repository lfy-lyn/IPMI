<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Program;
use App\Models\ProgramSession;
use App\Models\ProgramSessionAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProgramSessionController extends Controller
{
    /** Pastikan sesi benar-benar milik program di URL (cegah manipulasi ID) dan pengguna berwenang. */
    private function authorizeSession(Program $program, ProgramSession $session): void
    {
        if ((int) $session->program_id !== (int) $program->id) {
            abort(404);
        }
        if (!$program->canBeManagedBy(Auth::user()->member)) {
            abort(403, 'Anda tidak memiliki akses ke absensi program ini.');
        }
    }

    public function store(Request $request, Program $program)
    {
        if (!$program->canBeManagedBy(Auth::user()->member)) {
            abort(403);
        }
        if ($program->status !== 'active') {
            return back()->with('error', 'Program nonaktif tidak dapat diberi sesi baru.');
        }

        $validated = $request->validate([
            'session_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'agenda' => 'nullable|string|max:500',
        ]);

        if ($program->sessions()->whereDate('session_date', $validated['session_date'])->exists()) {
            return back()->withErrors(['session_date' => 'Sudah ada sesi pada tanggal tersebut.'])->withInput();
        }

        $session = $program->sessions()->create($validated);
        AuditLog::record('program.session_created', $session, "Sesi {$program->name} ({$session->session_date->format('d/m/Y')}) dibuat");

        return redirect()->route('programs.sessions.attendance', [$program, $session])->with('success', 'Sesi dibuat. Silakan catat kehadiran.');
    }

    public function attendance(Program $program, ProgramSession $session)
    {
        $this->authorizeSession($program, $session);

        $members = $program->eligibleMembers()->with('division')->orderBy('full_name')->get();
        $attendances = $session->attendances()->get()->keyBy('member_id');

        return view('admin.programs.attendance', compact('program', 'session', 'members', 'attendances'));
    }

    public function record(Request $request, Program $program, ProgramSession $session)
    {
        $this->authorizeSession($program, $session);

        $validated = $request->validate([
            'attendances' => 'required|array|min:1',
            'attendances.*.member_id' => 'required|integer|distinct',
            'attendances.*.status' => 'required|in:hadir,izin,sakit,alpa',
            'attendances.*.notes' => 'nullable|string|max:500',
        ]);

        $eligible = $program->eligibleMembers()->pluck('id')->all();
        $submitted = array_map('intval', array_column($validated['attendances'], 'member_id'));
        if (array_diff($submitted, $eligible)) {
            return back()->withErrors(['attendances' => 'Ada anggota yang bukan peserta program ini. Absensi tidak disimpan.']);
        }

        DB::transaction(function () use ($validated, $session, $program) {
            foreach ($validated['attendances'] as $row) {
                $rec = ProgramSessionAttendance::firstOrNew(['program_session_id' => $session->id, 'member_id' => (int) $row['member_id']]);
                $notes = $row['notes'] ?? null;

                if ($rec->exists && ($rec->status !== $row['status'] || $rec->notes !== $notes)) {
                    AuditLog::record('attendance.changed', $rec, 'Absensi program rutin diubah', [
                        'program_id' => $program->id, 'session_id' => $session->id,
                        'member_id' => $rec->member_id, 'dari' => $rec->status, 'ke' => $row['status'],
                    ]);
                }
                $rec->fill(['status' => $row['status'], 'notes' => $notes])->save();
            }
            AuditLog::record('attendance.recorded', $session, "Absensi sesi {$program->name} disimpan");
        });

        return back()->with('success', 'Absensi sesi berhasil dicatat.');
    }
}
