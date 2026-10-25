<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of academic years.
     */
    public function index()
    {
        // Only Ketua Umum and Sekretaris can access
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->paginate(15);

        return view('admin.academic-years.index', compact('academicYears'));
    }

    /**
     * Show the form for creating a new academic year.
     */
    public function create()
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403);
        }

        return view('admin.academic-years.create');
    }

    /**
     * Store a newly created academic year.
     */
    public function store(Request $request)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:academic_years,name',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:upcoming,active,archived',
        ]);

        // Check if setting status to active when another year is already active
        if ($validated['status'] === 'active') {
            $existingActive = AcademicYear::where('status', 'active')->first();
            if ($existingActive) {
                return back()->withErrors(['status' => 'Sudah ada tahun ajaran aktif. Nonaktifkan tahun ajaran tersebut terlebih dahulu.'])->withInput();
            }
        }

        AcademicYear::create($validated);

        return redirect()->route('academic-years.index')
                        ->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the academic year.
     */
    public function edit(AcademicYear $academicYear)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403);
        }

        return view('admin.academic-years.edit', compact('academicYear'));
    }

    /**
     * Update the academic year.
     */
    public function update(Request $request, AcademicYear $academicYear)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:academic_years,name,' . $academicYear->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:upcoming,active,archived',
        ]);

        // Check if changing status to active when another year is already active
        if ($validated['status'] === 'active' && $academicYear->status !== 'active') {
            $existingActive = AcademicYear::where('status', 'active')
                                          ->where('id', '!=', $academicYear->id)
                                          ->first();
            if ($existingActive) {
                return back()->withErrors(['status' => 'Sudah ada tahun ajaran aktif. Nonaktifkan tahun ajaran tersebut terlebih dahulu.'])->withInput();
            }
        }

        $academicYear->update($validated);

        return redirect()->route('academic-years.index')
                        ->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    /**
     * Activate an academic year (deactivates others).
     */
    public function activate(AcademicYear $academicYear)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403);
        }

        $academicYear->activate();
        \App\Models\AuditLog::record('academic_year.activated', $academicYear, "Tahun ajaran {$academicYear->name} diaktifkan");

        return redirect()->route('academic-years.index')
                        ->with('success', 'Tahun ajaran berhasil diaktifkan.');
    }

    /**
     * Archive an academic year.
     */
    public function archive(AcademicYear $academicYear)
    {
        $member = Auth::user()->member;
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403);
        }

        if ($academicYear->status === 'active') {
            $academicYear->update(['status' => 'archived']);
            \App\Models\AuditLog::record('academic_year.archived', $academicYear, "Tahun ajaran {$academicYear->name} diarsipkan");
            return redirect()->route('academic-years.index')
                            ->with('success', 'Tahun ajaran berhasil diarsipkan.');
        }

        return redirect()->route('academic-years.index')
                        ->with('error', 'Hanya tahun ajaran aktif yang dapat diarsipkan.');
    }
}
