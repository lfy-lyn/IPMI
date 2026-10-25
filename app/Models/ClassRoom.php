<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'academic_year_id',
        'grade_level',
        'major',
        'class_number',
        'name',
        'status',
    ];

    /**
     * Get the academic year that owns the class.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the member class histories for this class.
     */
    public function memberHistories()
    {
        return $this->hasMany(MemberClassHistory::class, 'class_id');
    }

    /**
     * Get all members in this class.
     */
    public function members()
    {
        return $this->belongsToMany(Member::class, 'member_class_histories', 'class_id', 'member_id')
                    ->withTimestamps();
    }

    /**
     * Generate display name from components.
     */
    public static function generateName($gradeLevel, $major, $classNumber)
    {
        return "{$gradeLevel} {$major} {$classNumber}";
    }
}
