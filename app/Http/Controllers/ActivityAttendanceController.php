<?php

namespace App\Http\Controllers;

use App\Models\ActivityAttendance;
use App\Models\AuditLog;
use App\Models\IncidentalActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActivityAttendanceController extends Controller
{
    private function authorizeManage(IncidentalActivity $activity): void
    {
        if (!$activity->canManageAttendanceBy(Auth::user()->member)) {
            abort(403, 'Anda tidak memiliki akses ke absensi kegiatan ini.');
        }
    }

    public function index(Request $request, IncidentalActivity $activity)
    {
        $this->authorizeManage($activity);

        $activity->load(['division', 'activityDays']);

        // Kegiatan multi-hari mode per_day: absensi dicatat per hari terpilih.
        $selectedDay = null;
        if ($activity->attendance_mode === 'per_day') {
            $dayId = $request->integer('day') ?: $activity->activityDays->sortBy('date')->first()?->id;
            $selectedDay = $activity->activityDays->firstWhere('id', $dayId);
        }

        $members = $activity->eligibleMembers()->with('division')->orderBy('full_name')->get();

        $attendances = ActivityAttendance::where('incidental_activity_id', $activity->id)
            ->when($activity->attendance_mode === 'per_day', fn ($q) => $q->where('activity_day_id', $selectedDay?->id))
            ->when($activity->attendance_mode === 'per_event', fn ($q) => $q->whereNull('activity_day_id'))
            ->get()
            ->keyBy('member_id');

        return view('admin.activities.attendance', compact('activity', 'members', 'attendances', 'selectedDay'));
    }

    public function record(Request $request, IncidentalActivity $activity)
    {
        $this->authorizeManage($activity);

        $validated = $request->validate([
            'activity_day_id' => 'nullable|integer',
            'attendances' => 'required|array|min:1',
            'attendances.*.member_id' => 'required|integer|distinct',
            'attendances.*.status' => 'required|in:hadir,izin,sakit,alpa',
            'attendances.*.notes' => 'nullable|string|max:500',
        ]);

        // Konsistensi mode absensi (D-029): hari wajib untuk per_day, dilarang untuk per_event,
        // dan hari harus milik kegiatan ini.
        $dayId = null;
        if ($activity->attendance_mode === 'per_day') {
            $dayId = $validated['activity_day_id'] ?? null;
            if (!$dayId || !$activity->activityDays()->whereKey($dayId)->exists()) {
                return back()->withErrors(['activity_day_id' => 'Pilih hari kegiatan yang valid untuk mencatat absensi.']);
            }
        } elseif (!empty($validated['activity_day_id'])) {
            return back()->withErrors(['activity_day_id' => 'Kegiatan ini memakai absensi per kegiatan, bukan per hari.']);
        }

        // Cegah IDOR: hanya anggota yang memang menjadi sasaran kegiatan ini.
        $eligibleIds = $activity->eligibleMembers()->pluck('id')->all();
        $submittedIds = array_map('intval', array_column($validated['attendances'], 'member_id'));
        $outsiders = array_diff($submittedIds, $eligibleIds);
        if ($outsiders) {
            return back()->withErrors(['attendances' => 'Ada anggota yang bukan peserta kegiatan ini. Absensi tidak disimpan.']);
        }

        DB::transaction(function () use ($validated, $activity, $dayId) {
            $changes = 0;
            foreach ($validated['attendances'] as $row) {
                $record = ActivityAttendance::firstOrNew([
                    'member_id' => (int) $row['member_id'],
                    'incidental_activity_id' => $activity->id,
                    'activity_day_id' => $dayId,
                ]);
                $notes = $row['notes'] ?? null;

                if ($record->exists && ($record->status !== $row['status'] || $record->notes !== $notes)) {
                    // Perubahan atas catatan yang sudah ada dicatat (D-017).
                    AuditLog::record('attendance.changed', $record, 'Absensi kegiatan diubah', [
                        'kegiatan_id' => $activity->id,
                        'member_id' => $record->member_id,
                        'dari' => $record->status,
                        'ke' => $row['status'],
                    ]);
                }

                $record->fill(['status' => $row['status'], 'notes' => $notes])->save();
                $changes++;
            }

            AuditLog::record('attendance.recorded', $activity, "Absensi {$activity->name} disimpan ({$changes} anggota)", [
                'activity_day_id' => $dayId,
            ]);
        });

        return back()->with('success', 'Absensi berhasil dicatat.');
    }

    public function update(Request $request, ActivityAttendance $attendance)
    {
        $activity = $attendance->incidentalActivity;
        $this->authorizeManage($activity);

        $validated = $request->validate([
            'status' => 'required|in:hadir,izin,sakit,alpa',
            'notes' => 'nullable|string|max:500',
        ]);

        $from = $attendance->status;
        $attendance->update($validated);

        AuditLog::record('attendance.changed', $attendance, 'Absensi kegiatan diubah', [
            'kegiatan_id' => $activity->id,
            'member_id' => $attendance->member_id,
            'dari' => $from,
            'ke' => $validated['status'],
        ]);

        return back()->with('success', 'Absensi berhasil diperbarui.');
    }

    public function summary(IncidentalActivity $activity)
    {
        $this->authorizeManage($activity);

        $activity->load(['division', 'activityDays']);

        $attendances = ActivityAttendance::where('incidental_activity_id', $activity->id)
            ->with(['member.division', 'activityDay'])
            ->get();

        $summary = [
            'hadir' => $attendances->where('status', 'hadir')->count(),
            'izin' => $attendances->where('status', 'izin')->count(),
            'sakit' => $attendances->where('status', 'sakit')->count(),
            'alpa' => $attendances->where('status', 'alpa')->count(),
        ];

        $perMember = $attendances->groupBy('member_id')->map(function ($rows) {
            return [
                'member' => $rows->first()->member,
                'hadir' => $rows->where('status', 'hadir')->count(),
                'izin' => $rows->where('status', 'izin')->count(),
                'sakit' => $rows->where('status', 'sakit')->count(),
                'alpa' => $rows->where('status', 'alpa')->count(),
            ];
        })->sortBy(fn ($r) => $r['member']->full_name)->values();

        return view('admin.activities.summary', compact('activity', 'attendances', 'summary', 'perMember'));
    }
}
