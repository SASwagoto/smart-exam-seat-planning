<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSeat extends Model
{
    protected $fillable = [
        'exam_session_room_id',
        'exam_session_course_id',
        'student_id',
        'row_label',
        'column_label',
        'seat_label',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(ExamSessionRoom::class, 'exam_session_room_id');
    }

    public function sessionCourse(): BelongsTo
    {
        return $this->belongsTo(ExamSessionCourse::class, 'exam_session_course_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
