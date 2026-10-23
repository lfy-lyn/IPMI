<?php

namespace App\Http\Controllers;

use App\Models\MembershipApplication;
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

        $emailSent = false;

        try {
            DB::transaction(function () use ($application, $user, &$emailSent) {
                // Update application status
                $application->update([
                    'status' => 'approved',
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                ]);

                // Check if user already exists
                $newUser = User::where('email', $application->email)->first();
                
                if (!$newUser) {
                    // Create user account with pending status
                    $newUser = User::create([
                        'email' => $application->email,
                        'password' => Hash::make(uniqid()), // Temporary, will be set during activation
                        'status' => 'pending',
                    ]);
                }

                // Check if member already exists
                $newMember = Member::where('user_id', $newUser->id)->first();
                
                if (!$newMember) {
                    // Create member record
                    $newMember = Member::create([
                        'user_id' => $newUser->id,
                        'nis' => $application->nis,
                        'full_name' => $application->full_name,
                        'division_id' => $application->division_id,
                        'phone' => $application->phone,
                        'status' => 'active',
                    ]);
                }

                // Generate activation token
                $token = ActivationToken::generate($newMember);

                // Send activation email (catch exception to prevent rollback)
                try {
                    Mail::to($application->email)->send(new AccountActivationMail($token, $application));
                    $emailSent = true;
                } catch (\Exception $e) {
                    \Log::error('Failed to send activation email: ' . $e->getMessage());
                    // Don't throw - let transaction commit
                }
            });

            if ($emailSent) {
                return redirect()->route('admin.membership.show', $application)
                    ->with('success', 'Pendaftaran berhasil disetujui. Email aktivasi telah dikirim ke ' . $application->email);
            } else {
                return redirect()->route('admin.membership.show', $application)
                    ->with('warning', 'Pendaftaran berhasil disetujui, tetapi email aktivasi gagal dikirim. Mohon periksa konfigurasi email.');
            }
                
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
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
