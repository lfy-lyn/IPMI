<?php

namespace App\Http\Controllers;

use App\Models\MembershipApplication;
use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MembershipReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $member = $user->member;

        // Only Ketua Umum and Sekretaris can review
        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403, 'Anda tidak memiliki akses untuk meninjau pendaftaran.');
        }

        $status = $request->get('status');

        $applications = MembershipApplication::with(['division', 'reviewer'])
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.membership.index', compact('applications', 'status'));
    }

    public function show(MembershipApplication $application)
    {
        $user = Auth::user();
        $member = $user->member;

        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403, 'Anda tidak memiliki akses untuk melihat detail pendaftaran.');
        }

        $application->load(['division', 'reviewer']);

        return view('admin.membership.show', compact('application'));
    }

    public function approve(MembershipApplication $application)
    {
        $user = Auth::user();
        $member = $user->member;

        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403, 'Anda tidak memiliki akses untuk menyetujui pendaftaran.');
        }

        if ($application->status !== 'pending') {
            return back()->with('error', 'Pendaftaran ini sudah diproses sebelumnya.');
        }

        DB::transaction(function () use ($application, $user) {
            $application->update([
                'status' => 'approved',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);
        });

        return redirect()->route('admin.membership.show', $application)
            ->with('success', 'Pendaftaran berhasil disetujui. Sistem aktivasi akun akan diimplementasikan pada tahap berikutnya.');
    }

    public function reject(Request $request, MembershipApplication $application)
    {
        $user = Auth::user();
        $member = $user->member;

        if (!$member || (!$member->isKetuaUmum() && !$member->isSekretaris())) {
            abort(403, 'Anda tidak memiliki akses untuk menolak pendaftaran.');
        }

        if ($application->status !== 'pending') {
            return back()->with('error', 'Pendaftaran ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        DB::transaction(function () use ($application, $validated, $user) {
            $application->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);
        });

        return redirect()->route('admin.membership.show', $application)
            ->with('success', 'Pendaftaran berhasil ditolak.');
    }
}