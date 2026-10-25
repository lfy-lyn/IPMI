<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassRoom;
use App\Models\MemberClassHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClassPromotionController extends Controller
{
    /**
     * Show class promotion setup page.
     */
    public function index()
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return view('admin.class-promotion.index', compact('academicYears'));
    }

    /**
     * Preview class promotion mapping.
     */
    public function preview(Request $request)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403);
        }

        $validated = $request->validate([
            'source_year_id' => 'required|exists:academic_years,id',
            'target_year_id' => 'required|exists:academic_years,id|different:source_year_id',
        ]);

        $sourceYear = AcademicYear::findOrFail($validated['source_year_id']);
        $targetYear = AcademicYear::findOrFail($validated['target_year_id']);

        // Get source classes with member count
        $sourceClasses = ClassRoom::where('academic_year_id', $sourceYear->id)
                                  ->withCount(['memberHistories'])
                                  ->orderBy('grade_level')
                                  ->orderBy('major')
                                  ->orderBy('class_number')
                                  ->get();

        // Get target classes
        $targetClasses = ClassRoom::where('academic_year_id', $targetYear->id)
                                  ->where('status', 'active')
                                  ->orderBy('grade_level')
                                  ->orderBy('major')
                                  ->orderBy('class_number')
                                  ->get();

        return view('admin.class-promotion.preview', compact('sourceYear', 'targetYear', 'sourceClasses', 'targetClasses'));
    }

    /**
     * Process class promotion.
     */
    public function process(Request $request)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403);
        }

        $validated = $request->validate([
            'source_year_id' => 'required|exists:academic_years,id',
            'target_year_id' => 'required|exists:academic_years,id|different:source_year_id',
            'mappings' => 'required|array',
            'mappings.*' => 'nullable|integer|exists:classes,id',
        ]);

        $sourceYear = AcademicYear::findOrFail($validated['source_year_id']);
        $targetYear = AcademicYear::findOrFail($validated['target_year_id']);

        if ($targetYear->status === 'archived') {
            return back()->withErrors(['target_year_id' => 'Tahun ajaran tujuan sudah diarsipkan.'])->withInput();
        }

        // Kelas tanpa tujuan = tidak dinaikkan (mis. kelas XII yang lulus).
        $mappings = array_filter($validated['mappings'], fn ($v) => $v !== null && $v !== '');

        if (empty($mappings)) {
            return back()->withErrors(['mappings' => 'Pilih minimal satu kelas tujuan.'])->withInput();
        }

        // Kelas asal harus milik tahun asal; kelas tujuan harus milik tahun tujuan dan aktif.
        $sourceIds = array_map('intval', array_keys($mappings));
        $validSource = ClassRoom::whereIn('id', $sourceIds)->where('academic_year_id', $sourceYear->id)->count();
        if ($validSource !== count(array_unique($sourceIds))) {
            return back()->withErrors(['mappings' => 'Ada kelas asal yang bukan dari tahun ajaran asal.'])->withInput();
        }

        $targetIds = array_values(array_unique(array_map('intval', $mappings)));
        $validTarget = ClassRoom::whereIn('id', $targetIds)
            ->where('academic_year_id', $targetYear->id)->where('status', 'active')->count();
        if ($validTarget !== count($targetIds)) {
            return back()->withErrors(['mappings' => 'Semua kelas tujuan harus aktif dan berasal dari tahun ajaran tujuan.'])->withInput();
        }

        // Anggota yang sudah punya kelas di tahun tujuan dilewati (tidak membatalkan seluruh proses).
        $alreadyPlaced = MemberClassHistory::whereHas('classRoom', fn ($q) => $q->where('academic_year_id', $targetYear->id))
            ->pluck('member_id')->flip();

        try {
            $result = DB::transaction(function () use ($mappings, $alreadyPlaced, $sourceYear, $targetYear) {
                $promoted = 0;
                $skipped = 0;

                foreach ($mappings as $sourceClassId => $targetClassId) {
                    $histories = MemberClassHistory::with('member')->where('class_id', $sourceClassId)->get();

                    foreach ($histories as $history) {
                        if (!$history->member || $history->member->status !== 'active' || $alreadyPlaced->has($history->member_id)) {
                            $skipped++;
                            continue;
                        }

                        // Riwayat tahun asal TIDAK diubah; hanya menambah riwayat tahun tujuan.
                        MemberClassHistory::create([
                            'member_id' => $history->member_id,
                            'class_id' => (int) $targetClassId,
                        ]);
                        $promoted++;
                    }
                }

                AuditLog::record('class.promotion', $targetYear, "Kenaikan kelas {$sourceYear->name} -> {$targetYear->name}: {$promoted} naik, {$skipped} dilewati", [
                    'source_year_id' => $sourceYear->id,
                    'promoted' => $promoted,
                    'skipped' => $skipped,
                ]);

                return [$promoted, $skipped];
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['error' => 'Terjadi kesalahan saat memproses kenaikan kelas. Tidak ada data yang diubah.'])->withInput();
        }

        [$promoted, $skipped] = $result;

        return redirect()->route('class-promotion.index')
            ->with('success', "Kenaikan kelas berhasil. {$promoted} anggota dinaikkan, {$skipped} dilewati (nonaktif atau sudah punya kelas di tahun tujuan).");
    }
}
