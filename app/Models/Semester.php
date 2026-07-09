<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $fillable = [
        'name', 'code',
    ];

    public function academicSessions()
    {
        return $this->hasMany(AcademicSession::class);
    }
}
