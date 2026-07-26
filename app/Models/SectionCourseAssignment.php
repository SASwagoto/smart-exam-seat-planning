<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SectionCourseAssignment extends Model
{
    protected $fillable = [
        'academic_session_id',
        'department_id',
        'batch_id',
        'section_id',
    ];

    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function batch(): BelongsTo { return $this->belongsTo(Batch::class); }
    public function section(): BelongsTo { return $this->belongsTo(Section::class); }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    // ড্রপডাউনে সুন্দর করে দেখানোর জন্য accessor (ঐচ্ছিক কিন্তু সাজেস্টেড)
    public function getFullLabelAttribute(): string
    {
        $sectionName = $this->section?->name ?? 'N/A';
        $courseCode = $this->course?->code ?? $this->course?->name ?? 'N/A';

        return "{$sectionName} — {$courseCode}";
    }

    public function items(): HasMany
    {
        return $this->hasMany(SectionCourseAssignmentItem::class);
    }
}