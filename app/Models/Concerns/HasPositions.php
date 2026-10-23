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
     * Check if current member can view another member
     */
    public function canViewMember(Member $member): bool
    {
        // Ketua Umum and Sekretaris can view all members
        if ($this->isKetuaUmum() || $this->isSekretaris()) {
            return true;
        }

        // Ketua Divisi can view members in their division
        if ($this->isKetuaDivisi() && $this->division_id === $member->division_id) {
            return true;
        }

        // Members can view themselves
        if ($this->id === $member->id) {
            return true;
        }

        // By default, members can view other members in same division
        return $this->division_id === $member->division_id;
    }
}