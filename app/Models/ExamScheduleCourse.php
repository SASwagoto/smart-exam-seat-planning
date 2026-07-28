<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamScheduleCourse extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function examSchedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class);
    }

    public function sectionCourseAssignment(): BelongsTo
    {
        return $this->belongsTo(SectionCourseAssignment::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
