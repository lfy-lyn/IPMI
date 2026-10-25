<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Pengelolaan daftar kelas (rombel) per tahun ajaran.
 * Hanya Ketua Umum dan Sekretaris (D-005, authorization_matrix).
 */
class ClassRoomController extends Controller
{
    private function authorizeManage(): void
    {
        $member = Auth::user()->member;

        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola kelas.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeManage();

        $query = ClassRoom::with('academicYear')->withCount('memberHistories');

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->integer('academic_year_id'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->string('search') . '%');
        }

        $classes = $query->orderBy('academic_year_id', 'desc')
            ->orderBy('grade_level')
            ->orderBy('major')
            ->orderBy('class_number')
            ->paginate(20);

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return view('admin.classes.index', compact('classes', 'academicYears'));
    }

    public function create()
    {
        $this->authorizeManage();

        $academicYears = AcademicYear::where('status', '!=', 'archived')->orderBy('start_date', 'desc')->get();

        return view('admin.classes.create', compact('academicYears'));
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $validated = $this->validated($request);

        $year = AcademicYear::findOrFail($validated['academic_year_id']);
        if ($year->status === 'archived') {
            return back()->withErrors(['academic_year_id' => 'Tahun ajaran yang sudah diarsipkan tidak dapat diberi kelas baru.'])->withInput();
        }

        $validated['name'] = ClassRoom::generateName($validated['grade_level'], $validated['major'], $validated['class_number']);

        $class = ClassRoom::create($validated);

        AuditLog::record('class.created', $class, "Kelas {$class->name} dibuat ({$year->name}).");

        return redirect()->route('classes.index')->with('success', "Kelas {$class->name} berhasil ditambahkan.");
    }

    public function edit(ClassRoom $class)
    {
        $this->authorizeManage();

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $hasMembers = $class->memberHistories()->exists();

        return view('admin.classes.edit', compact('class', 'academicYears', 'hasMembers'));
    }

    public function update(Request $request, ClassRoom $class)
    {
        $this->authorizeManage();

        $validated = $this->validated($request, $class);

        // Riwayat kelas tidak boleh berubah makna: jika sudah ada anggota,
        // identitas kelas (tahun, tingkat, jurusan, nomor) dikunci. Hanya status yang boleh diubah.
        if ($class->memberHistories()->exists()) {
            $identityChanged = (int) $validated['academic_year_id'] !== (int) $class->academic_year_id
                || $validated['grade_level'] !== $class->grade_level
                || $validated['major'] !== $class->major
                || (int) $validated['class_number'] !== (int) $class->class_number;

            if ($identityChanged) {
                return back()->withErrors([
                    'grade_level' => 'Kelas ini sudah memiliki anggota/riwayat sehingga tahun ajaran, tingkat, jurusan, dan nomor tidak dapat diubah. Hanya status yang bisa diubah.',
                ])->withInput();
            }
        }

        $validated['name'] = ClassRoom::generateName($validated['grade_level'], $validated['major'], $validated['class_number']);

        $before = $class->only(['academic_year_id', 'grade_level', 'major', 'class_number', 'name', 'status']);
        $class->update($validated);

        AuditLog::record('class.updated', $class, "Kelas {$class->name} diperbarui.", [
            'sebelum' => $before,
            'sesudah' => $class->only(['academic_year_id', 'grade_level', 'major', 'class_number', 'name', 'status']),
        ]);

        return redirect()->route('classes.index')->with('success', "Kelas {$class->name} berhasil diperbarui.");
    }

    private function validated(Request $request, ?ClassRoom $class = null): array
    {
        // Rapikan input sebelum validasi: "x " -> "X", " rpl" -> "RPL"
        $request->merge([
            'grade_level' => strtoupper(trim((string) $request->input('grade_level'))),
            'major' => strtoupper(trim((string) $request->input('major'))),
        ]);

        return $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'grade_level' => ['required', 'string', 'max:10', Rule::in(['X', 'XI', 'XII'])],
            'major' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9 \-]+$/'],
            'class_number' => [
                'required', 'integer', 'min:1', 'max:99',
                Rule::unique('classes')->where(fn ($q) => $q
                    ->where('academic_year_id', $request->input('academic_year_id'))
                    ->where('grade_level', $request->input('grade_level'))
                    ->where('major', $request->input('major')))
                    ->ignore($class?->id),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'grade_level.in' => 'Tingkat harus X, XI, atau XII.',
            'major.regex' => 'Jurusan hanya boleh berisi huruf, angka, spasi, atau tanda minus.',
            'class_number.unique' => 'Kelas dengan tahun ajaran, tingkat, jurusan, dan nomor yang sama sudah ada.',
        ]);
    }
}
