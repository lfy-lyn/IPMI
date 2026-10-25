<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MembershipApplication;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Member;
use App\Models\ActivationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountActivationMail;

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

        // Jangan pernah menautkan ke akun/anggota yang sudah ada hanya karena email sama.
        $application->email = strtolower($application->email);
        if (User::where('email', $application->email)->exists() || Member::where('nis', $application->nis)->exists()) {
            return back()->with('error', 'Email atau NIS pendaftar ini sudah dipakai akun/anggota lain, sehingga tidak dapat disetujui otomatis. Tolak pendaftaran ini atau selesaikan duplikasi data terlebih dahulu.');
        }

        $token = null;

        try {
            DB::transaction(function () use ($application, $user, &$token) {
                // Kunci baris agar dua pengurus tidak menyetujui bersamaan.
                $locked = MembershipApplication::whereKey($application->id)->lockForUpdate()->first();
                if ($locked->status !== 'pending') {
                    throw new \RuntimeException('already-processed');
                }

                $newUser = User::create([
                    'email' => $application->email,
                    'password' => Hash::make(Str::random(40)), // diganti saat aktivasi
                    'status' => 'pending',
                ]);

                $newMember = Member::create([
                    'user_id' => $newUser->id,
                    'nis' => $application->nis,
                    'full_name' => $application->full_name,
                    'division_id' => $application->division_id,
                    'phone' => $application->phone,
                    'status' => 'active',
                ]);

                $locked->update([
                    'status' => 'approved',
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                    'member_id' => $newMember->id,
                ]);

                $token = ActivationToken::generate($newMember);

                AuditLog::record('membership.approved', $locked, "Pendaftaran {$locked->full_name} disetujui", [
                    'member_id' => $newMember->id,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Pendaftaran ini sudah diproses sebelumnya.');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Terjadi kesalahan saat menyetujui pendaftaran. Tidak ada data yang diubah. Silakan coba lagi.');
        }

        // Email dikirim setelah data tersimpan; kegagalan email tidak membatalkan persetujuan.
        $emailSent = false;
        try {
            Mail::to($application->email)->send(new AccountActivationMail($token, $application));
            $emailSent = true;
        } catch (\Throwable $e) {
            report($e);
        }

        if ($emailSent) {
            return redirect()->route('admin.membership.show', $application)
                ->with('success', 'Pendaftaran disetujui. Email aktivasi dikirim ke ' . $application->email);
        }

        return redirect()->route('admin.membership.show', $application)
            ->with('warning', 'Pendaftaran disetujui, tetapi email aktivasi GAGAL dikirim (email belum dikonfigurasi atau bermasalah). Periksa pengaturan MAIL_* di .env.');
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

            AuditLog::record('membership.rejected', $application, "Pendaftaran {$application->full_name} ditolak", [
                'alasan' => $validated['rejection_reason'],
            ]);
        });

        return redirect()->route('admin.membership.show', $application)
            ->with('success', 'Pendaftaran berhasil ditolak.');
    }
}
