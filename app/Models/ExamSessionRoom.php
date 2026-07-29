<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSessionRoom extends Model
{
    protected $fillable = [
        'exam_session_id',
        'room_id',
        'columns',
        'allocated_students',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(ExamSeat::class);
    }
}
