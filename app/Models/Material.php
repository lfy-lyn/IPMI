<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'division_id',
        'title',
        'description',
        'file_path',
        'external_url',
        'status',
        'created_by',
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

        // Ketua Umum & Sekretaris can access all
        if ($member->isKetuaUmum() || $member->isSekretaris()) {
            return true;
        }

        // Ketua Divisi can access own division materials
        if ($member->isKetuaDivisi()) {
            return (int) $this->division_id === (int) $member->division_id;
        }

        // Regular members can view materials from their division
        return (int) $this->division_id === (int) $member->division_id && $this->status === 'active';
    }

    public function hasFile()
    {
        return !empty($this->file_path);
    }

    public function hasUrl()
    {
        return !empty($this->external_url);
    }
}
