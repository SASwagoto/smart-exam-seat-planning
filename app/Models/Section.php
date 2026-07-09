<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $fillable = ['batch_id', 'section_name', 'capacity'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function teacherCourseAssignments()
    {
        return $this->hasMany(TeacherCourseAssignment::class);
    }
}
