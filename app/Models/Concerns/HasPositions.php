<?php

namespace App\Models\Concerns;

use App\Models\Position;
use App\Models\Member;

trait HasPositions
{
    public function positions()
    {
        return $this->belongsToMany(Position::class, 'member_positions');
    }

    public function hasPosition(string $positionName): bool
    {
        return $this->positions()->where('name', $positionName)->exists();
    }

    public function isKetuaUmum(): bool
    {
        return $this->hasPosition('Ketua Umum');
    }

    public function isSekretaris(): bool
    {
        return $this->hasPosition('Sekretaris');
    }

    public function isKetuaDivisi(): bool
    {
        return $this->hasPosition('Ketua Divisi');
    }

    public function canManageDivision(int $divisionId): bool
    {
        // Ketua Umum dan Sekretaris bisa manage semua divisi
        if ($this->isKetuaUmum() || $this->isSekretaris()) {
            return true;
        }

        // Ketua Divisi hanya bisa manage divisi sendiri
        if ($this->isKetuaDivisi()) {
            return $this->division_id === $divisionId;
        }

        return false;
    }

    public function canAccessAllDivisions(): bool
    {
        return $this->isKetuaUmum() || $this->isSekretaris();
    }

    /**
     * Pengurus pusat = Ketua Umum atau Sekretaris.
     */
    public function isPengurusPusat(): bool
    {
        return $this->isKetuaUmum() || $this->isSekretaris();
    }

    /**
     * Semua anggota aktif boleh melihat profil terbatas anggota aktif lain
     * (nama, kelas, divisi, jabatan) sesuai D-012. Anggota nonaktif hanya
     * dapat dilihat oleh yang berwenang atas data pribadinya.
     */
    public function canViewMember(Member $member): bool
    {
        if ($this->canViewPrivateDataOf($member)) {
            return true;
        }

        return $this->status === 'active' && $member->status === 'active';
    }

    /**
     * Data pribadi (NIS, email, telepon, status akun) hanya untuk:
     * Ketua Umum/Sekretaris, Ketua Divisi atas anggota divisinya, dan diri sendiri.
     */
    public function canViewPrivateDataOf(Member $member): bool
    {
        if ($this->isPengurusPusat()) {
            return true;
        }

        if ($this->getKey() === $member->getKey()) {
            return true;
        }

        return $this->isKetuaDivisi() && (int) $this->division_id === (int) $member->division_id;
    }
}
