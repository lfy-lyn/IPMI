<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\IncidentalActivity;
use Illuminate\Support\Carbon;
use App\Models\Division;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IncidentalActivityController extends Controller
{
    public function index(Request $request)
    {
        $member = Auth::user()->member;
        if (!$member) {
            abort(403, 'Anda harus terdaftar sebagai anggota.');
        }

        $query = IncidentalActivity::with(['division', 'creator.member']);

        // Filter based on authorization
        if (!$member->isKetuaUmum() && !$member->isSekretaris()) {
            $query->where(function($q) use ($member) {
                $q->whereNull('division_id')
                  ->orWhere('division_id', $member->division_id);
            });
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('division_id')) {
            if ($request->division_id === 'all') {
                $query->whereNull('division_id');
            } else {
                $query->where('division_id', $request->division_id);
            }
        }

        $activities = $query->orderBy('start_date', 'desc')->paginate(15);
        $divisions = Division::where('status', 'active')->get();

        return view('admin.activities.index', compact('activities', 'divisions'));
    }

    public function create()
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris() && !$member->isKetuaDivisi())) {
            abort(403, 'Anda tidak memiliki akses untuk membuat kegiatan.');
        }

        $divisions = Division::where('status', 'active')->get();
        return view('admin.activities.create', compact('divisions'));
    }

    public function store(Request $request)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris() && !$member->isKetuaDivisi())) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'division_id' => 'nullable|exists:divisions,id',
            'attendance_mode' => 'required|in:per_event,per_day',
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
        ]);

        // Ketua Divisi hanya bisa membuat kegiatan untuk divisinya sendiri (bukan lintas divisi).
        if ($member->isKetuaDivisi() && !$member->isKetuaUmum() && !$member->isSekretaris()) {
            if (!empty($validated['division_id']) && (int) $validated['division_id'] !== (int) $member->division_id) {
                abort(403, 'Anda hanya dapat membuat kegiatan untuk divisi Anda.');
            }
            $validated['division_id'] = $member->division_id;
        }

        if ($validated['attendance_mode'] === 'per_day' && count($this->dateRange($validated['start_date'], $validated['end_date'] ?? null)) > 31) {
            return back()->withErrors(['end_date' => 'Kegiatan per hari maksimal 31 hari.'])->withInput();
        }

        $validated['created_by'] = Auth::id();

        $activity = DB::transaction(function () use ($validated) {
            $activity = IncidentalActivity::create($validated);
            $this->syncDays($activity);
            AuditLog::record('activity.created', $activity, "Kegiatan {$activity->name} dibuat");

            return $activity;
        });

        return redirect()->route('activities.index')->with('success', 'Kegiatan berhasil dibuat.');
    }

    public function show(IncidentalActivity $activity)
    {
        $member = Auth::user()->member;
        if (!$activity->canBeAccessedBy($member)) {
            abort(403, 'Anda tidak memiliki akses ke kegiatan ini.');
        }

        $activity->load(['division', 'creator.member', 'activityDays']);
        $canManageAttendance = $activity->canManageAttendanceBy($member);
        $attendanceCounts = $canManageAttendance
            ? $activity->attendances()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')
            : collect();

        return view('admin.activities.show', compact('activity', 'canManageAttendance', 'attendanceCounts'));
    }

    public function edit(IncidentalActivity $activity)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris() && !$member->isKetuaDivisi())) {
            abort(403);
        }

        if ($member->isKetuaDivisi() && !$member->isKetuaUmum() && !$member->isSekretaris()) {
            if ($activity->division_id != $member->division_id) {
                abort(403, 'Anda hanya dapat mengedit kegiatan divisi Anda.');
            }
        }

        $divisions = Division::where('status', 'active')->get();
        return view('admin.activities.edit', compact('activity', 'divisions'));
    }

    public function update(Request $request, IncidentalActivity $activity)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris() && !$member->isKetuaDivisi())) {
            abort(403);
        }

        if ($member->isKetuaDivisi() && !$member->isKetuaUmum() && !$member->isSekretaris()) {
            if ($activity->division_id != $member->division_id) {
                abort(403);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'division_id' => 'nullable|exists:divisions,id',
            'attendance_mode' => 'required|in:per_event,per_day',
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
        ]);

        // Ketua Divisi tidak boleh memindahkan kegiatan ke divisi lain atau menjadikannya lintas divisi.
        if ($member->isKetuaDivisi() && !$member->isKetuaUmum() && !$member->isSekretaris()) {
            if (!empty($validated['division_id']) && (int) $validated['division_id'] !== (int) $member->division_id) {
                abort(403);
            }
            $validated['division_id'] = $member->division_id;
        }

        if ($validated['attendance_mode'] === 'per_day' && count($this->dateRange($validated['start_date'], $validated['end_date'] ?? null)) > 31) {
            return back()->withErrors(['end_date' => 'Kegiatan per hari maksimal 31 hari.'])->withInput();
        }

        DB::transaction(function () use ($activity, $validated) {
            $activity->update($validated);
            $this->syncDays($activity->fresh());
            AuditLog::record('activity.updated', $activity, "Kegiatan {$activity->name} diperbarui");
        });

        return redirect()->route('activities.show', $activity)->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function archive(IncidentalActivity $activity)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403, 'Hanya Ketua Umum dan Sekretaris yang dapat mengarsipkan kegiatan.');
        }

        $activity->update(['status' => 'cancelled']);

        return redirect()->route('activities.index')->with('success', 'Kegiatan berhasil diarsipkan.');
    }

    /**
     * Daftar tanggal (Y-m-d) dari mulai sampai selesai (atau hanya mulai bila selesai kosong).
     */
    private function dateRange($start, $end): array
    {
        $from = Carbon::parse($start)->startOfDay();
        $to = $end ? Carbon::parse($end)->startOfDay() : $from->copy();

        $dates = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $dates[] = $d->toDateString();
        }

        return $dates;
    }

    /**
     * Untuk absensi per hari: bentuk hari kegiatan dari rentang tanggal.
     * Hari yang sudah punya catatan absensi tidak pernah dihapus.
     */
    private function syncDays(IncidentalActivity $activity): void
    {
        if ($activity->attendance_mode !== 'per_day') {
            return;
        }

        $wanted = $this->dateRange($activity->start_date, $activity->end_date);
        $existing = $activity->activityDays()->get();

        foreach ($wanted as $date) {
            if (!$existing->contains(fn ($day) => $day->date->toDateString() === $date)) {
                $activity->activityDays()->create(['date' => $date]);
            }
        }

        foreach ($existing as $day) {
            if (!in_array($day->date->toDateString(), $wanted, true) && !$day->attendances()->exists()) {
                $day->delete();
            }
        }
    }
}
