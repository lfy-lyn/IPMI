<?php

namespace App\Http\Controllers;

use App\Models\MembershipApplication;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MembershipApplicationController extends Controller
{
    public function create()
    {
        $divisions = Division::where('status', 'active')->get();
        return view('membership.create', compact('divisions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:20'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'division_id' => ['required', 'exists:divisions,id'],
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
        ]);

        // Check batas 3 pendaftaran per NIS
        $existingCount = MembershipApplication::countPendingOrApprovedByNis($validated['nis']);
        
        if ($existingCount >= 3) {
            throw ValidationException::withMessages([
                'nis' => ['NIS ini sudah memiliki 3 pendaftaran yang sedang diproses. Silakan hubungi pengurus untuk informasi lebih lanjut.'],
            ]);
        }

        $application = MembershipApplication::create($validated);

        return redirect()->route('membership.success')
            ->with('success', 'Pendaftaran berhasil dikirim. Pengurus akan meninjau pendaftaran Anda.');
    }

    public function success()
    {
        return view('membership.success');
    }
}