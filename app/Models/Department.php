<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['name', 'code'];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function teacherCourseAssignments()
    {
        return $this->hasMany(TeacherCourseAssignment::class);
    }
}
