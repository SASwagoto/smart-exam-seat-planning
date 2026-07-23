<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherCourseAssignment extends Model
{
    protected $fillable = [
        'academic_session_id',
        'department_id',
        'teacher_id',
        'course_id',
    ];

    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
}
