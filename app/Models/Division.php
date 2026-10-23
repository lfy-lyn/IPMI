<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }
}
