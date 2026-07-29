<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSessionCourse extends Model
{
    protected $fillable = [
        'exam_session_id',
        'batch_id',
        'course_id',
        'section_course_assignment_id',
        'section_course_assignment_item_id',
        'total_students',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function sectionCourseAssignment(): BelongsTo
    {
        return $this->belongsTo(SectionCourseAssignment::class);
    }

    public function sectionCourseAssignmentItem(): BelongsTo
    {
        return $this->belongsTo(SectionCourseAssignmentItem::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(ExamSeat::class);
    }
}
