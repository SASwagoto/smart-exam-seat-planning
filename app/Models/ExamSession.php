<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    protected $fillable = [
        'exam_id',
        'exam_date',
        'exam_slot_id',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function examSlot(): BelongsTo
    {
        return $this->belongsTo(ExamSlot::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(ExamSessionCourse::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(ExamSessionRoom::class);
    }
}
