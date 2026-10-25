<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'incidental_activity_id',
        'date',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function incidentalActivity()
    {
        return $this->belongsTo(IncidentalActivity::class);
    }

    public function attendances()
    {
        return $this->hasMany(ActivityAttendance::class);
    }
}
