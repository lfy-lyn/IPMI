<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramSession extends Model
{
    protected $fillable = ['program_id', 'session_date', 'start_time', 'agenda'];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function attendances()
    {
        return $this->hasMany(ProgramSessionAttendance::class);
    }
}
