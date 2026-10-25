<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'incidental_activity_id',
        'activity_day_id',
        'status',
        'notes',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function incidentalActivity()
    {
        return $this->belongsTo(IncidentalActivity::class);
    }

    public function activityDay()
    {
        return $this->belongsTo(ActivityDay::class);
    }
}
