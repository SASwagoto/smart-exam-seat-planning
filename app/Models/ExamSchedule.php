<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSchedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function examSlot(): BelongsTo
    {
        return $this->belongsTo(ExamSlot::class);
    }

    public function scheduleCourses(): HasMany
    {
        return $this->hasMany(ExamScheduleCourse::class);
    }

    public function scheduleRooms(): HasMany
    {
        return $this->hasMany(ExamScheduleRoom::class);
    }

    public function seatAllocations(): HasMany
    {
        return $this->hasMany(SeatAllocation::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'exam_schedule_rooms');
    }
}
