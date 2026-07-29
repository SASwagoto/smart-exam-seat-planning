<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollmentCourse extends Model
{
    protected $fillable = [
        'student_course_enrollment_id',
        'academic_session_id',
        'batch_id',
        'student_id',
        'course_id',
        'enrollment_type',
        'status',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function studentCourseEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentCourseEnrollment::class, 'student_course_enrollment_id');
    }
}
