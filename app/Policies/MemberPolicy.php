<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->member !== null;
    }

    public function view(User $user, Member $member): bool
    {
        // Semua anggota bisa lihat profil anggota lain (terbatas)
        return $user->member !== null;
    }

    public function create(User $user): bool
    {
        // Hanya Ketua Umum dan Sekretaris
        return $user->member 
            && ($user->member->isKetuaUmum() || $user->member->isSekretaris());
    }

    public function update(User $user, Member $member): bool
    {
        // Ketua Umum dan Sekretaris bisa update semua
        if ($user->member && ($user->member->isKetuaUmum() || $user->member->isSekretaris())) {
            return true;
        }

        // Ketua Divisi bisa update anggota divisinya
        if ($user->member && $user->member->isKetuaDivisi()) {
            return $user->member->division_id === $member->division_id;
        }

        // Anggota hanya bisa update diri sendiri (terbatas)
        return $user->member && $user->member->id === $member->id;
    }

    public function delete(User $user, Member $member): bool
    {
        // Hanya Ketua Umum dan Sekretaris
        return $user->member 
            && ($user->member->isKetuaUmum() || $user->member->isSekretaris());
    }

    public function changeNis(User $user, Member $member): bool
    {
        // Hanya Ketua Umum dan Sekretaris
        return $user->member 
            && ($user->member->isKetuaUmum() || $user->member->isSekretaris());
    }

    public function changeDivision(User $user, Member $member): bool
    {
        // Hanya Ketua Umum dan Sekretaris
        return $user->member 
            && ($user->member->isKetuaUmum() || $user->member->isSekretaris());
    }

    public function deactivate(User $user, Member $member): bool
    {
        // Ketua Umum dan Sekretaris bisa deactivate semua
        if ($user->member && ($user->member->isKetuaUmum() || $user->member->isSekretaris())) {
            return true;
        }

        // Ketua Divisi bisa deactivate anggota divisinya
        if ($user->member && $user->member->isKetuaDivisi()) {
            return $user->member->division_id === $member->division_id;
        }

        return false;
    }
}