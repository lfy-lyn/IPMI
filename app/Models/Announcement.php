<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'target_type',
        'division_id',
        'status',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canBeAccessedBy($member)
    {
        if (!$member) {
            return false;
        }

        // Ketua Umum & Sekretaris dapat melihat semua (termasuk draft/arsip untuk dikelola).
        if ($member->isKetuaUmum() || $member->isSekretaris()) {
            return true;
        }

        // Selain pengurus pusat: hanya pengumuman yang sudah terbit dan sudah waktunya.
        if (!$this->isLive()) {
            return false;
        }

        switch ($this->target_type) {
            case 'all':
                return true;
            case 'division':
                return (int) $this->division_id === (int) $member->division_id;
            case 'pengurus':
                return $member->isKetuaDivisi();
            default:
                return false;
        }
    }

    /**
     * Sudah terbit dan waktu terbitnya sudah tiba.
     */
    public function isLive(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function isPublished()
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }
}
