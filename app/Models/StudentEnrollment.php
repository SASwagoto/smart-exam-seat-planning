<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'academic_session_id',
        'department_id',
        'batch_id',
        'section_id',
        'course_ids',
        'student_id',
        'status',
    ];

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function courses()
    {
        return $this->belongsToMany(
            Course::class,
            'student_enrollment_courses'
        );
    }

    public function enrollmentCourses()
    {
        return $this->hasMany(StudentEnrollmentCourse::class);
    }
}
