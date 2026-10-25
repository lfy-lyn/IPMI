<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramSessionAttendance extends Model
{
    protected $fillable = ['program_session_id', 'member_id', 'status', 'notes'];

    public function session()
    {
        return $this->belongsTo(ProgramSession::class, 'program_session_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
