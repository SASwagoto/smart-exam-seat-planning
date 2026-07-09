<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'course_code',
        'course_title',
        'credit',
        'type',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function teacherCourseAssignments()
    {
        return $this->hasMany(TeacherCourseAssignment::class);
    }

    public function studentEnrollments()
    {
        return $this->belongsToMany(
            StudentEnrollment::class,
            'student_enrollment_courses'
        );
    }

    public function enrollmentCourses()
    {
        return $this->hasMany(StudentEnrollmentCourse::class);
    }
}
