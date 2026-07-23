<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentCourseEnrollment extends Model
{
    protected $fillable = [
        'academic_session_id',
        'student_id',
        'status',
    ];

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollmentCourses(): HasMany
    {
        return $this->hasMany(StudentEnrollmentCourse::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'student_enrollment_courses')
            ->withPivot(['enrollment_type', 'status'])
            ->withTimestamps();
    }
}