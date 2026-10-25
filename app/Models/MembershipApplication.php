<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'nis',
        'full_name',
        'email',
        'phone',
        'address',
        'division_id',
        'reason',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'member_id',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function countPendingOrApprovedByNis(string $nis): int
    {
        return self::where('nis', $nis)
            ->whereIn('status', ['pending', 'approved'])
            ->count();
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
