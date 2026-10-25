<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AcademicYear extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the classes for this academic year.
     */
    public function classes()
    {
        return $this->hasMany(ClassRoom::class);
    }

    /**
     * Check if this is the active academic year.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Get the currently active academic year.
     */
    public static function getActive()
    {
        return static::where('status', 'active')->first();
    }

    /**
     * Ensure only one academic year can be active at a time.
     */
    public function activate()
    {
        \DB::transaction(function () {
            // Deactivate all other academic years
            static::where('status', 'active')->update(['status' => 'archived']);
            
            // Activate this one
            $this->status = 'active';
            $this->save();
        });
    }
}
