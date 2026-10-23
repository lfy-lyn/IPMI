<?php

namespace App\Models\Concerns;

use App\Models\Position;

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
}