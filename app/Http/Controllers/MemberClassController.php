<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\MemberClassHistory;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberClassController extends Controller
{
    public function index(Request $request)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $activeYear = AcademicYear::where('status', 'active')->first();
        
        if (!$activeYear) {
            return redirect()->route('academic-years.index')
                            ->with('error', 'Belum ada tahun ajaran aktif. Aktifkan tahun ajaran terlebih dahulu.');
        }

        $query = Member::with(['division', 'classHistories.classRoom.academicYear'])
                      ->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        if ($request->filled('has_class')) {
            if ($request->has_class === 'yes') {
                $query->whereHas('classHistories.classRoom', function($q) use ($activeYear) {
                    $q->where('academic_year_id', $activeYear->id);
                });
            } else {
                $query->whereDoesntHave('classHistories.classRoom', function($q) use ($activeYear) {
                    $q->where('academic_year_id', $activeYear->id);
                });
            }
        }

        $members = $query->paginate(20);
        
        $divisions = \App\Models\Division::where('status', 'active')->get();
        $classes = ClassRoom::where('academic_year_id', $activeYear->id)
                           ->where('status', 'active')
                           ->orderBy('grade_level')
                           ->orderBy('major')
                           ->orderBy('class_number')
                           ->get();

        return view('admin.member-classes.index', compact('members', 'activeYear', 'divisions', 'classes'));
    }

    public function assign(Request $request, Member $member)
    {
        $user = Auth::user()->member;
        if (!$user || (!$user->isKetuaUmum() && !$user->isSekretaris())) {
            abort(403);
        }

        $validated = $request->validate([
            'class_id' => 'required|integer|exists:classes,id',
            'correction_reason' => 'nullable|string|min:10|max:500',
        ], [
            'correction_reason.min' => 'Alasan koreksi minimal 10 karakter.',
        ]);

        if ($member->status !== 'active') {
            return back()->with('error', 'Anggota tidak aktif, kelas tidak dapat ditetapkan.');
        }

        $class = ClassRoom::findOrFail($validated['class_id']);

        $activeYear = AcademicYear::where('status', 'active')->first();
        if (!$activeYear || (int) $class->academic_year_id !== (int) $activeYear->id) {
            return back()->withErrors(['class_id' => 'Kelas harus dari tahun ajaran aktif.']);
        }

        if ($class->status !== 'active') {
            return back()->withErrors(['class_id' => 'Kelas ini berstatus nonaktif.']);
        }

        $existing = MemberClassHistory::where('member_id', $member->id)
            ->whereHas('classRoom', fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->with('classRoom')
            ->first();

        if ($existing) {
            // D-025: tidak boleh pindah rombel di tengah tahun ajaran.
            // Hanya koreksi data yang salah, wajib beralasan dan tercatat di audit log.
            if ((int) $existing->class_id === (int) $class->id) {
                return back()->with('error', 'Anggota sudah berada di kelas tersebut.');
            }

            if (empty($validated['correction_reason'])) {
                return back()->withErrors([
                    'correction_reason' => 'Anggota sudah punya kelas di tahun ajaran ini. Perubahan hanya boleh sebagai koreksi data salah dan wajib disertai alasan (min. 10 karakter).',
                ])->withInput();
            }

            DB::transaction(function () use ($existing, $class, $member, $validated) {
                $oldName = $existing->classRoom?->name;
                $existing->update(['class_id' => $class->id]);

                AuditLog::record('member.class_corrected', $member, "Koreksi kelas {$member->full_name}: {$oldName} -> {$class->name}", [
                    'dari_kelas_id' => $existing->getOriginal('class_id'),
                    'ke_kelas_id' => $class->id,
                    'alasan' => $validated['correction_reason'],
                ]);
            });

            return back()->with('success', 'Kelas anggota dikoreksi dan dicatat di audit log.');
        }

        DB::transaction(function () use ($member, $class) {
            MemberClassHistory::create([
                'member_id' => $member->id,
                'class_id' => $class->id,
            ]);

            AuditLog::record('member.class_assigned', $member, "Kelas {$member->full_name} ditetapkan: {$class->name}", [
                'kelas_id' => $class->id,
            ]);
        });

        return back()->with('success', 'Kelas anggota berhasil ditetapkan.');
    }

    public function remove(Request $request, Member $member)
    {
        $user = Auth::user()->member;
        if (!$user || (!$user->isKetuaUmum() && !$user->isSekretaris())) {
            abort(403);
        }

        $validated = $request->validate([
            'correction_reason' => 'required|string|min:10|max:500',
        ], [
            'correction_reason.required' => 'Alasan penghapusan penetapan kelas wajib diisi.',
            'correction_reason.min' => 'Alasan minimal 10 karakter.',
        ]);

        $activeYear = AcademicYear::where('status', 'active')->first();

        if (!$activeYear) {
            return back()->with('error', 'Belum ada tahun ajaran aktif.');
        }

        $history = MemberClassHistory::where('member_id', $member->id)
            ->whereHas('classRoom', fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->with('classRoom')
            ->first();

        if (!$history) {
            return back()->with('error', 'Anggota tidak memiliki kelas pada tahun ajaran aktif.');
        }

        DB::transaction(function () use ($history, $member, $validated) {
            AuditLog::record('member.class_removed', $member, "Penetapan kelas {$member->full_name} ({$history->classRoom?->name}) dihapus sebagai koreksi", [
                'kelas_id' => $history->class_id,
                'alasan' => $validated['correction_reason'],
            ]);
            $history->delete();
        });

        return back()->with('success', 'Penetapan kelas dihapus sebagai koreksi dan dicatat di audit log.');
    }
}
