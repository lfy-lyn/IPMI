<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Kegiatan rutin/berulang (pembiasaan pagi, ekskul, latihan Hadroh, dst). */
class Program extends Model
{
    protected $fillable = ['name', 'description', 'schedule_note', 'division_id', 'status', 'created_by'];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions()
    {
        return $this->hasMany(ProgramSession::class);
    }

    /** Ketua Umum/Sekretaris: semua. Ketua Divisi: hanya program divisinya. */
    public function canBeManagedBy($member): bool
    {
        if (!$member) {
            return false;
        }
        if ($member->isPengurusPusat()) {
            return true;
        }

        return $member->isKetuaDivisi()
            && $this->division_id !== null
            && (int) $this->division_id === (int) $member->division_id;
    }

    /** Semua anggota aktif melihat program seluruh IPMI + divisinya; pengurus pusat melihat semua. */
    public function canBeViewedBy($member): bool
    {
        if (!$member) {
            return false;
        }
        if ($member->isPengurusPusat()) {
            return true;
        }

        return $this->status === 'active'
            && ($this->division_id === null || (int) $this->division_id === (int) $member->division_id);
    }

    /** Anggota aktif yang menjadi sasaran program. */
    public function eligibleMembers()
    {
        return Member::where('status', 'active')
            ->when($this->division_id, fn ($q) => $q->where('division_id', $this->division_id));
    }
}
