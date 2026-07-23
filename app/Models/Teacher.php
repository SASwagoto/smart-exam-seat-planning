<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Teacher extends Model
{
    protected $fillable = [
        'teacher_id',
        'name',
        'email',
        'phone',
        'designation',
        'status',
    ];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'teacher_course_assignments')
            ->withPivot(['academic_session_id', 'department_id'])
            ->withTimestamps();
    }
}
