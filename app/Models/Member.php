<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasPositions;

class Member extends Model
{
    use HasFactory, HasPositions;

    protected $fillable = [
        'user_id',
        'nis',
        'full_name',
        'division_id',
        'phone',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function classHistories()
    {
        return $this->hasMany(MemberClassHistory::class);
    }

    public function programSessionAttendances()
    {
        return $this->hasMany(ProgramSessionAttendance::class);
    }

    public function activityAttendances()
    {
        return $this->hasMany(ActivityAttendance::class);
    }
}
