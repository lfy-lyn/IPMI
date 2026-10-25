<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Program;
use App\Models\ProgramSessionAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProgramController extends Controller
{
    private function member()
    {
        $m = Auth::user()->member;
        if (!$m) {
            abort(403, 'Anda harus terdaftar sebagai anggota.');
        }

        return $m;
    }

    private function mustBeManager(): \App\Models\Member
    {
        $m = $this->member();
        if (!$m->isPengurusPusat() && !$m->isKetuaDivisi()) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola program rutin.');
        }

        return $m;
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'schedule_note' => 'nullable|string|max:255',
            'division_id' => 'nullable|integer|exists:divisions,id',
            'status' => 'required|in:active,inactive',
        ];
    }

    /** Ketua Divisi hanya boleh program divisinya sendiri (bukan seluruh IPMI). */
    private function scopeDivision(\App\Models\Member $m, array $validated): array
    {
        if (!$m->isPengurusPusat()) {
            if (!empty($validated['division_id']) && (int) $validated['division_id'] !== (int) $m->division_id) {
                abort(403, 'Anda hanya dapat mengelola program divisi Anda.');
            }
            $validated['division_id'] = $m->division_id;
        }

        return $validated;
    }

    public function index()
    {
        $m = $this->member();

        $programs = Program::with('division')->withCount('sessions')
            ->when(!$m->isPengurusPusat(), fn ($q) => $q->where('status', 'active')
                ->where(fn ($w) => $w->whereNull('division_id')->orWhere('division_id', $m->division_id)))
            ->orderBy('name')->paginate(15);

        return view('admin.programs.index', compact('programs'));
    }

    public function create()
    {
        $m = $this->mustBeManager();
        $divisions = Division::where('status', 'active')->get();

        return view('admin.programs.create', compact('divisions', 'm'));
    }

    public function store(Request $request)
    {
        $m = $this->mustBeManager();
        $validated = $this->scopeDivision($m, $request->validate($this->rules()));
        $validated['created_by'] = Auth::id();

        $program = Program::create($validated);
        AuditLog::record('program.created', $program, "Program rutin {$program->name} dibuat");

        return redirect()->route('programs.show', $program)->with('success', 'Program rutin berhasil dibuat. Sekarang tambahkan sesi pertama.');
    }

    public function show(Program $program)
    {
        $m = $this->member();
        if (!$program->canBeViewedBy($m)) {
            abort(403, 'Anda tidak memiliki akses ke program ini.');
        }

        $canManage = $program->canBeManagedBy($m);
        $program->load('division');
        $sessions = $program->sessions()->withCount('attendances')->orderByDesc('session_date')->get();

        $recap = collect();
        if ($canManage) {
            $recap = ProgramSessionAttendance::whereIn('program_session_id', $sessions->pluck('id'))
                ->select('member_id', 'status', DB::raw('count(*) as total'))
                ->groupBy('member_id', 'status')->get()
                ->groupBy('member_id')
                ->map(function ($rows) {
                    $by = $rows->pluck('total', 'status');

                    return ['hadir' => (int) ($by['hadir'] ?? 0), 'izin' => (int) ($by['izin'] ?? 0), 'sakit' => (int) ($by['sakit'] ?? 0), 'alpa' => (int) ($by['alpa'] ?? 0)];
                });
            $names = \App\Models\Member::whereIn('id', $recap->keys())->pluck('full_name', 'id');
            $recap = $recap->map(fn ($r, $id) => $r + ['name' => $names[$id] ?? '-'])->sortBy('name')->values();
        }

        return view('admin.programs.show', compact('program', 'sessions', 'canManage', 'recap'));
    }

    public function edit(Program $program)
    {
        $m = $this->mustBeManager();
        if (!$program->canBeManagedBy($m)) {
            abort(403);
        }
        $divisions = Division::where('status', 'active')->get();

        return view('admin.programs.edit', compact('program', 'divisions', 'm'));
    }

    public function update(Request $request, Program $program)
    {
        $m = $this->mustBeManager();
        if (!$program->canBeManagedBy($m)) {
            abort(403);
        }

        $validated = $this->scopeDivision($m, $request->validate($this->rules()));

        // Mengubah sasaran divisi setelah ada presensi akan mengacaukan data kehadiran.
        if (array_key_exists('division_id', $validated)
            && (int) ($validated['division_id'] ?? 0) !== (int) ($program->division_id ?? 0)
            && $program->sessions()->whereHas('attendances')->exists()) {
            return back()->withErrors(['division_id' => 'Sasaran divisi tidak dapat diubah karena program ini sudah memiliki catatan kehadiran.'])->withInput();
        }

        $program->update($validated);
        AuditLog::record('program.updated', $program, "Program rutin {$program->name} diperbarui");

        return redirect()->route('programs.show', $program)->with('success', 'Program rutin berhasil diperbarui.');
    }
}
