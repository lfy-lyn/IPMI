<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberClassHistory extends Model
{
    protected $fillable = [
        'member_id',
        'class_id',
    ];

    /**
     * Get the member that owns the history.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the class for this history.
     */
    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    /**
     * Get the academic year through the class.
     */
    public function academicYear()
    {
        return $this->hasOneThrough(
            AcademicYear::class,
            ClassRoom::class,
            'id', // Foreign key on classes table
            'id', // Foreign key on academic_years table
            'class_id', // Local key on member_class_histories table
            'academic_year_id' // Local key on classes table
        );
    }
}
