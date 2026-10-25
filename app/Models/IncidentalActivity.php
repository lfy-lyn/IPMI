<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentalActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'division_id',
        'attendance_mode',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activityDays()
    {
        return $this->hasMany(ActivityDay::class);
    }

    public function attendances()
    {
        return $this->hasMany(ActivityAttendance::class);
    }

    public function isForAllMembers()
    {
        return $this->division_id === null;
    }

    public function canBeAccessedBy($member)
    {
        if (!$member) {
            return false;
        }

        if ($member->isKetuaUmum() || $member->isSekretaris()) {
            return true;
        }

        if ($member->isKetuaDivisi()) {
            return $this->division_id === $member->division_id;
        }

        return $this->isForAllMembers() || $this->division_id === $member->division_id;
    }

    /**
     * Siapa yang boleh MENGELOLA/MELIHAT absensi (data pribadi, D-012):
     * Ketua Umum & Sekretaris (semua kegiatan) dan Ketua Divisi (hanya kegiatan divisinya sendiri).
     * Anggota biasa tidak boleh melihat lembar absensi.
     */
    public function canManageAttendanceBy($member): bool
    {
        if (!$member) {
            return false;
        }

        if ($member->isKetuaUmum() || $member->isSekretaris()) {
            return true;
        }

        return $member->isKetuaDivisi()
            && $this->division_id !== null
            && (int) $this->division_id === (int) $member->division_id;
    }

    /**
     * Anggota aktif yang menjadi sasaran kegiatan ini.
     */
    public function eligibleMembers()
    {
        return Member::where('status', 'active')
            ->when($this->division_id, fn ($q) => $q->where('division_id', $this->division_id));
    }
}
