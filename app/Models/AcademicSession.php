<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    protected $fillable = [
        'semester_id',
        'year',
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected static function booted()
    {
        static::saving(function ($academicSession) {

            $academicSession->name =
                $academicSession->semester->name.' '.$academicSession->year;

        });
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function teacherCourseAssignments()
    {
        return $this->hasMany(TeacherCourseAssignment::class);
    }
}
