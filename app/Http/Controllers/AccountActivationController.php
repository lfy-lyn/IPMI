<?php

namespace App\Http\Controllers;

use App\Models\ActivationToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountActivationController extends Controller
{
    public function show(string $token)
    {
        $activationToken = ActivationToken::where('token', $token)->first();

        if (!$activationToken) {
            return view('auth.activation', [
                'error' => 'Token aktivasi tidak valid.',
                'token' => null,
            ]);
        }

        if (!$activationToken->isValid()) {
            $reason = $activationToken->used_at 
                ? 'Token ini sudah digunakan.' 
                : 'Token aktivasi sudah kedaluwarsa.';
                
            return view('auth.activation', [
                'error' => $reason,
                'token' => null,
            ]);
        }

        return view('auth.activation', [
            'error' => null,
            'token' => $token,
            'email' => $activationToken->member->user->email,
            'fullName' => $activationToken->member->full_name,
        ]);
    }

    public function activate(Request $request, string $token)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $activationToken = ActivationToken::where('token', $token)->first();

        if (!$activationToken || !$activationToken->isValid()) {
            return back()->with('error', 'Token aktivasi tidak valid atau sudah kedaluwarsa.');
        }

        try {
            DB::transaction(function () use ($activationToken, $validated) {
                $user = $activationToken->member->user;

                // Set password and activate account
                $user->update([
                    'password' => Hash::make($validated['password']),
                    'status' => 'active',
                ]);

                // Mark token as used
                $activationToken->markAsUsed();
            });

            return redirect()->route('activation.success');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan saat mengaktifkan akun. Silakan coba lagi.');
        }
    }

    public function success()
    {
        return view('auth.activation-success');
    }
}